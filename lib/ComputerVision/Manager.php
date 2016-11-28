<?php
/**
 * Created by PhpStorm.
 * User: mona
 * Date: 18/08/16
 * Time: 12:45
 */

namespace ComputerVision;


use Pimcore\Model\Asset;


class Manager
{
    const PROCESSED = 'cv_processed';
    const PROCESSED_VALUE = 'processed';

    private $client;
    private $asset;
    private $type;
    private $delay = 0;
    private $data;
            

    public function __construct($assetId, $type, $delay = 0)
    {
        $this->asset = Asset::getById($assetId);
        
        if (!$this->asset) {
            throw new \Exception('Asset does not exist');
        }
        
        $this->type = $type;
        $this->delay = (int) $delay;
        $this->client = new \ComputerVision\ComputerVisionClient();
    }

    public function getData()
    {
        try {
            if ($this->type == 'folder') {
                $this->processFolder($this->asset);
                return true;
            }

            if (!($this->asset instanceof \Pimcore\Model\Asset\Image)) {
                throw new \Exception('Asset is not an image');
            }

            $this->processImage($this->asset);
            return true;
            
        } catch (\Exception $e) {
            throw $e;
        }
                
    }    

    public function saveData(Asset $asset, $data)
    {
        try {
            if (!is_array($data)) {
                throw new \Exception('no data');
            }
            
            foreach ($data as $key => $arr) {
                $preparedDataArray = $this->client->prepareData($key, $arr);

                if (!$preparedDataArray) {
                    continue;
                }

                foreach ($preparedDataArray as $item) {
                    $asset->addMetadata($item->name, $item->type, $item->data);
                }
            }

            $asset->addMetadata(self::PROCESSED, 'input', self::PROCESSED_VALUE);
            $asset->save();


        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function processFolder(Asset $folder)
    {
        try {
            $childs = $folder->getChilds();

            if (!is_array($childs)) {
                return false;
            }

            foreach ($childs as $child) {
                if ($child->getType() == 'folder') {
                    $this->processFolder($child);
                }

                if ($child instanceof \Pimcore\Model\Asset\Image) {
                    $this->processImage($child);
                }

            }

        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function processImage(\Pimcore\Model\Asset\Image $asset)
    {
        try {
            $processed = $asset->getMetadata(self::PROCESSED);
                        
            if ($processed == self::PROCESSED_VALUE) {
                return true;
            }

            $data = $this->client->analyzeImage($asset);
            $this->saveData($asset, $data);
            sleep($this->delay);
            
            return true;

        } catch (\Exception $e) {
            throw $e;
        }
    }
}