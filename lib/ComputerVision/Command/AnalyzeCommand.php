<?php

namespace ComputerVision\Command;

use ComputerVision\Manager;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\Asset;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class AnalyzeCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('computervision:analyze')
            ->setDescription('analyzes the asset via Microsoft Computer Vision API')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'asset or asset folder id'
            )
            ->addArgument(
                'delay',
                InputArgument::REQUIRED,
                'delay in seconds'
            );
    }
    

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {

            $asset = Asset::getById($input->getArgument('id'));

            if ($asset == null || !($asset instanceof Asset)) {
                throw new \Exception("No asset with this id \n");
            }

            $manager = new Manager($asset->getId(), $asset->getType(), $input->getArgument('delay'));
            $manager->getData();

            echo "ok \n";

        } catch (\Exception $e) {
            echo $e->getMessage() . "\n";
        }



    }

}