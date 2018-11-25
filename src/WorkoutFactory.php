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
    public const TIMESTAMP_FORMAT = "Y-m-d H:i:s O";
    /*
     * ./Sport-sessions/{UUID.json}                 - Description of workout
     * ./Sport-sessions/Elevation-data/{UUID.json}  - Description of elevation
     * ./Sport-sessions/GPS-data/{UUID.json}        - Description of GPS
     * ./Sport-sessions/Heart-rate-data{UUID.json}  - Description of workout
     *
    */
    public function create(array $workout, array $gps, array $heartrates = null): ?Workout
    {
        $startAt = \DateTime::createFromFormat("U", $workout['start_time'] / 1000);
        if (! $startAt instanceof \DateTime)
            return null;

        $workout = new Workout($startAt);

        if ($heartrates) {
            uasort($heartrates, function($a, $b) {
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

            $workout->addPoint($longitude, $latitude, $altitude, $timestamp,$hr);
        }

        return $workout;
    }

    public function findClosestHeartrate(\DateTime $timestamp, array $sortedHeartrates, int $maxDelta = 5): ?string
    {
        $closestDiff = null;
        $closestHeartrate = null;
        foreach ($sortedHeartrates as $row) {
            $current = \DateTime::createFromFormat(self::TIMESTAMP_FORMAT,$row['timestamp']);
            $diff = $current->diff($timestamp);
            /*
             *     [y] => 2
                    [m] => 1
                    [d] => 1
                    [h] => 1
                    [i] => 4
                    [s] => 1
                    [f] => 0.251066
             */
            $diffSecs = (((($diff->y * 365 + $diff->m * 30 + $diff->d) * 24) + $diff->h) * 60 + $diff->m) * 60 + $diff->s;

            $absDiffSecs = abs($diffSecs);
            if($absDiffSecs <= $maxDelta && $absDiffSecs < $closestDiff) {
                $closestDiff = $diffSecs;
                $closestHeartrate = $row['heart_rate'];
            } elseif ($closestDiff && $closestDiff < $absDiffSecs) {
                return $closestHeartrate;
            }
        }

        return null;
    }

    /**
     * @return Workout[]
     */
    public function parseZip(string $zipFile): array
    {
        $zip = new \ZipArchive();
        $zip->open($zipFile);

        $workoutDetails = $zip->getFromName("Sport-sessions/$uuid.json");
        $elevation = $zip->getFromName("Sport-sessions/Elevation-data/$uuid.json");
        $gps = $zip->getFromName("Sport-sessions/GPS-data/$uuid.json");
        $heartRate = $zip->getFromName("Sport-sessions/Heart-rate-data/$uuid.json");

        if (!$workoutDetails)
            return null;

        $workoutDetails = json_decode($workoutDetails,true, 512, JSON_THROW_ON_ERROR);

    }
}