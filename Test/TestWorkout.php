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

        $count = $document->getElementsByTagName("trkpt")->count();
        self::assertEquals(1, $count);
    }

    public function testClosestHeartrate()
    {
        $factory = new WorkoutFactory();
        $heartrates = json_decode(file_get_contents(__DIR__ . "/Heartrate.json"),true);

        uasort($heartrates, function($a, $b) {
            $dA = \DateTime::createFromFormat(WorkoutFactory::TIMESTAMP_FORMAT, $a['timestamp']);
            $dB = \DateTime::createFromFormat(WorkoutFactory::TIMESTAMP_FORMAT, $b['timestamp']);
            return $dA <=> $dB;
        });

        $target = \DateTime::createFromFormat(WorkoutFactory::TIMESTAMP_FORMAT, "2018-11-17 13:28:24 +0100");
        $hr = $factory->findClosestHeartrate($target,$heartrates,5);

        self::assertEquals(132, $hr);
    }
}