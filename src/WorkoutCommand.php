<?php
/**
 * Created by PhpStorm.
 * User: richard
 * Date: 25.11.18
 * Time: 22:58
 */

namespace App;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WorkoutCommand extends Command
{
    private $workoutFactory;

    public function __construct()
    {
        parent::__construct("parse:workout");
        $this->addArgument("zipFile", InputArgument::REQUIRED);
        $this->workoutFactory = new WorkoutFactory();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $zipFile = $input->getArgument("zipFile");
        $uuids = $this->workoutFactory->getUuids($zipFile);
        $pb = new ProgressBar($output, count($uuids));
        $pb->setFormat("debug");

        foreach ($uuids as $uuid) {
            $pb->advance();
            $workout = $this->workoutFactory->parseZip($zipFile,$uuid);

            if (!$workout)
                continue;

            $xml = $workout->getDocument()->saveXML();
            file_put_contents($workout->getStartAt()->format("c").".gpx",$xml);
        }

        $pb->finish();
    }

}