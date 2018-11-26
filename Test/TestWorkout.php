<?php
/**
 * Created by PhpStorm.
 * User: richard
 * Date: 25.11.18
 * Time: 14:33
 */

namespace Test;


use App\Workout;
use App\WorkoutFactory;
use PHPUnit\Framework\TestCase;

class TestWorkout extends TestCase
{
    public function testXmlHeader()
    {
        $now = new \DateTime();
        $workout = new Workout($now);
        $content = $workout->getDocument()->saveXML();

        self::assertContains('<?xml version="1.0" encoding="UTF-8"?>', $content);
    }

    public function testAddPoint()
    {
        $now = new \DateTime();
        $workout = new Workout($now);
        $workout->addPoint("5.6612749099731445", "58.8948440551757812", "66.0", $now,"111.5");
        $document = $workout->getDocument();
        $content = $document->saveXML();

        self::assertContains('<trkpt lon="5.6612749099731445" lat="58.8948440551757812">', $content);
        self::assertContains('<ele>66.0</ele>', $content);
        self::assertContains('<gpxtpx:hr>111.5</gpxtpx:hr>', $content);
        self::assertContains('<type>running</type>', $content);

        $count = $document->getElementsByTagName("trkpt")->count();
        self::assertEquals(1, $count);
    }

    public function testClosestHeartrate()
    {
        $factory = new WorkoutFactory();
        $hrs = json_decode(file_get_contents(__DIR__ . "/Heartrate.json"),true);

        uasort($hrs, function($a, $b) {
            $dA = \DateTime::createFromFormat(WorkoutFactory::TIMESTAMP_FORMAT, $a['timestamp']);
            $dB = \DateTime::createFromFormat(WorkoutFactory::TIMESTAMP_FORMAT, $b['timestamp']);
            return $dA <=> $dB;
        });

        $t = \DateTime::createFromFormat(WorkoutFactory::TIMESTAMP_FORMAT, "2018-11-17 13:28:24 +0100");
        $t2 = \DateTime::createFromFormat(WorkoutFactory::TIMESTAMP_FORMAT, "2018-11-17 13:25:05 +0100");

        self::assertEquals(132, $factory->findClosestHeartrate($t,$hrs,100));
        self::assertEquals(131, $factory->findClosestHeartrate($t2,$hrs,100));
    }

    public function testCreateWorkout() {

        $factory = new WorkoutFactory();
        $hrs = json_decode(file_get_contents(__DIR__ . "/Heartrate.json"),true);
        $gps = json_decode(file_get_contents(__DIR__ . "/Gps.json"),true);
        $workoutData = json_decode(file_get_contents(__DIR__ . "/Workout.json"),true);

        $workout = $factory->createWorkout($workoutData, $gps,$hrs);
        self::assertNotNull($workout);

        $document = $workout->getDocument();
        $count = $document->getElementsByTagName("trkpt")->count();
        self::assertEquals(2414, $count);

        $content = $document->saveXML();
        self::assertInstanceOf("string", $content);
    }

    public function testCreateWorkout_withoutHr() {

        $factory = new WorkoutFactory();
        $gps = json_decode(file_get_contents(__DIR__ . "/Gps.json"),true);
        $workoutData = json_decode(file_get_contents(__DIR__ . "/Workout.json"),true);

        $workout = $factory->createWorkout($workoutData, $gps, []);
        self::assertNotNull($workout);

        $document = $workout->getDocument();
        $count = $document->getElementsByTagName("trkpt")->count();
        self::assertEquals(2414, $count);

        $content = $document->saveXML();
        self::assertNotNull( $content);
    }

    public function testParseZipFile()  {
        $factory = new WorkoutFactory();
        $zipFile = __DIR__ . "/export-20181118-000.zip";

        $workouts = [];
        $uuids = $factory->getUuids($zipFile);

        self::assertCount(216, $uuids);

        $workout = $factory->parseZip($zipFile,$uuids[count($uuids) - 1]);
        self::assertNotNull($workout);
    }
}