<?php
/**
 * Created by PhpStorm.
 * User: richard
 * Date: 25.11.18
 * Time: 14:13
 */

namespace App;


class WorkoutFactory
{
    public const SPORT_TYPE_RUNNING = 1;
    public const TIMESTAMP_FORMAT = "Y-m-d H:i:s O";

    /*
     * ./Sport-sessions/{UUID.json}                 - Description of workout
     * ./Sport-sessions/Elevation-data/{UUID.json}  - Description of elevation
     * ./Sport-sessions/GPS-data/{UUID.json}        - Description of GPS
     * ./Sport-sessions/Heart-rate-data{UUID.json}  - Description of workout
     *
    */
    public function createWorkout(array $workout, array $gps, array $heartrates): ?Workout
    {
        $startAt = \DateTime::createFromFormat("U", $workout['start_time'] / 1000);
        if (!$startAt instanceof \DateTime)
            return null;

        $workout = new Workout($startAt);

        if ($heartrates) {
            uasort($heartrates, function ($a, $b) {
                $dA = \DateTime::createFromFormat(self::TIMESTAMP_FORMAT, $a['timestamp']);
                $dB = \DateTime::createFromFormat(self::TIMESTAMP_FORMAT, $b['timestamp']);
                return $dA <=> $dB;
            });
        }


        foreach ($gps as $point) {

            $longitude = $point['longitude'];
            $latitude = $point['latitude'];
            $altitude = $point['altitude'];
            $timestamp = new \DateTime($point['timestamp']);
            $hr = $this->findClosestHeartrate($timestamp, $heartrates, 5);

            $workout->addPoint($longitude, $latitude, $altitude, $timestamp, $hr);
        }

        return $workout;
    }

    public function findClosestHeartrate(\DateTime $timestamp, array $sortedHeartrates, int $maxDelta = 5): ?string
    {
        $closestDiff = null;
        $closestHeartrate = null;
        foreach ($sortedHeartrates as $row) {
            $current = \DateTime::createFromFormat(self::TIMESTAMP_FORMAT, $row['timestamp']);
            $diff = $current->diff($timestamp);

            $diffDays = ($diff->y * 365) + ($diff->m * 30) + $diff->d;
            $diffHours = ($diffDays * 24) + $diff->h;
            $diffMinutes = $diffHours * 60 + $diff->i;
            $diffSecs = $diffMinutes * 60 + $diff->s;

            $absDiffSecs = abs($diffSecs);
            if ($absDiffSecs <= $maxDelta && ($absDiffSecs < $closestDiff || $closestDiff === null)) {
                $closestDiff = $diffSecs;
                $closestHeartrate = $row['heart_rate'];
            } elseif ($closestDiff !== null && $closestDiff < $absDiffSecs) {
                return $closestHeartrate;
            }
        }

        return null;
    }

    public function getUuids(string $zipFile): array
    {
        $zip = new \ZipArchive();
        $zip->open($zipFile);

        $UUIDs = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (strpos($filename, "Sport-sessions") !== false
                && strpos($filename, "GPS-data") === false
                && strpos($filename, "Heart-rate-data") === false
                && strpos($filename, "Elevation-data") === false
            ) {
                $UUIDs[] = substr($filename, 15, -5);
            }
        }

        $zip->close();
        return $UUIDs;
    }

    /**
     * @return Workout[]
     */
    public function parseZip(string $zipFile, $uuid): ?Workout
    {
        $zip = new \ZipArchive();
        $zip->open($zipFile);

        $workoutDetails = $zip->getFromName("Sport-sessions/$uuid.json");
        $gps = $zip->getFromName("Sport-sessions/GPS-data/$uuid.json");
        $heartRate = $zip->getFromName("Sport-sessions/Heart-rate-data/$uuid.json");

        if (!$workoutDetails || !$gps) {
            $zip->close();
            return null;
        }

        $workoutDetails = json_decode($workoutDetails, true);
        $gps = json_decode($gps, true);
        if ($heartRate)
            $heartRate = json_decode($heartRate, true);
        else
            $heartRate = [];

        $zip->close();
        return $this->createWorkout($workoutDetails, $gps, $heartRate);

    }
}