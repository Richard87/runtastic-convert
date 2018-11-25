<?php
/**
 * Created by PhpStorm.
 * User: richard
 * Date: 25.11.18
 * Time: 14:12
 */

namespace App;


class Workout
{
    public const SPORT_TYPE_RUNNING = 1;
    private $startAt;

    private const XSD_GPX = "http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd";
    private const XSD_GPX_EXTENSION = "http://www.garmin.com/xmlschemas/GpxExtensions/v3 https://www8.garmin.com/xmlschemas/GpxExtensionsv3.xsd";
    private const XSD_TRACK_POINT = "http://www.garmin.com/xmlschemas/TrackPointExtension/v1 https://www8.garmin.com/xmlschemas/TrackPointExtensionv1.xsd";

    private const NS_GPXTPX = "http://www.garmin.com/xmlschemas/TrackPointExtension/v1";
    private const NS_GPXX = "http://www.garmin.com/xmlschemas/GpxExtensions/v3";

    private $document;
    private $root;
    private $trk;
    private $trkseg;

    public function __construct(\DateTime $startAt)
    {
        $document = new \DOMDocument("1.0", "UTF-8");
        $root = $document->createElementNS("http://www.topografix.com/GPX/1/1","gpx");
        $root->setAttribute("xsi:schemaLocation", self::XSD_GPX . " " . self::XSD_GPX_EXTENSION . " " . self::XSD_TRACK_POINT);
        $root->setAttribute("creator","Runtastic: Life is short - live long, http://www.runtastic.com");
        $root->setAttribute("version", "1.1");
        $root->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $root->setAttribute("xmlns:gpxx", "http://www.garmin.com/xmlschemas/GpxExtensions/v3");
        $root->setAttribute("xmlns:gpxtpx", "http://www.garmin.com/xmlschemas/TrackPointExtension/v1");

        $metatada = $document->createElement("metadata");

        $copyright = $document->createElement("copyright");
        $copyright->setAttribute("author","www.runtastic.com");
        $copyright->appendChild($document->createElement("year", 2018));
        $copyright->appendChild($document->createElement("license", "http://www.runtastic.com"));
        $metatada->appendChild($copyright);

        $link = $document->createElement("link");
        $link->setAttribute("href", "http://www.runtastic.com");
        $link->appendChild($document->createElement("text","runtastic"));
        $metatada->appendChild($link);
        $metatada->appendChild($document->createElement("time", $startAt->format("c")));
        $root->appendChild($metatada);

        $trk = $document->createElement("trk");
        $this->trkseg  = $document->createElement("trkseg");
        $root->appendChild($trk);
        $trk->appendChild($this->trkseg);

        $document->appendChild($root);
        $this->document = $document;
    }

    public function addPoint(string $longitude, string $latitude, string $elevation, \DateTime $time, string $heartRate = null): void
    {
        $point = $this->document->createElement("trkpt");

        $point->setAttribute("lon", $longitude);
        $point->setAttribute("lat", $latitude);
        if ($elevation)
            $point->appendChild($this->document->createElement("ele",$elevation));

        $point->appendChild($this->document->createElement("time",$time->format("c")));

        if ($heartRate) {
            $ext = $this->document->createElement("extensions");
            $tpExt = $this->document->createElement("gpxtpx:TrackPointExtension");
            $tpHrExt = $this->document->createElement("gpxtpx:hr", $heartRate);

            $tpExt->appendChild($tpHrExt);
            $ext->appendChild($tpExt);
            $point->appendChild($ext);
        }

        $this->trkseg->appendChild($point);
    }

    public function getDocument(): \DOMDocument
    {
        return $this->document;
    }
}