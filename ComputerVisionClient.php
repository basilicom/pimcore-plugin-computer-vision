<?php
/**
 * Created by PhpStorm.
 * User: mona
 * Date: 18/08/16
 * Time: 12:43
 */

namespace ComputerVision;


use Pimcore\Model\Asset;
use GuzzleHttp\Exception\ClientException;

class ComputerVisionClient
{
    private $client;
    private $url = 'https://api.projectoxford.ai/vision/v1.0/analyze?visualFeatures=';
    private $features = 'Description,Tags,Faces,Categories,ImageType,Color,Adult';
    private $subscriptionKey; 
    private $metaPrefix = 'cv_';

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client();

        $config = new \Zend_Config_Xml(Plugin::getConfigName());
        $this->subscriptionKey = $config->computerVisionSubscriptionKey;

        if (empty($this->subscriptionKey) || $this->subscriptionKey == Plugin::SUBCRIPTIONKEY_DUMMY) {
            throw new \Exception('ComputerVision subcription key not found in ' . Plugin::getConfigName());
        }

    }

    public function analyzeImage(Asset $image)
    {

        $uri = $this->url . $this->features . '&subscription-key=' . $this->subscriptionKey;

        try {
            $response = $this->client->post($uri, array(
                'multipart' => array(array(
                    'name' => 'image',
                    'contents' => fopen($image->getFileSystemPath(), 'r')
                ))
            ));

            $result = $response->getBody();
            //\Logger::err("RESPONSE: " . serialize($result->getContents()));
            
            return json_decode($result, true);
            

        } catch (ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            \Logger::err("Computer Vision Error RESPONSE: " . serialize($responseBodyAsString) . ' - asset id: ' . serialize($image->getId()));
            throw new \Exception('Computer Vision Api Error - please check your log files');
        }


    }
    
    public function prepareData ($key, $value)
    {
        $method = 'get' . ucfirst($key);

        if (!method_exists($this, $method)) {
            return false;
        }

        return $this->$method($key, $value);
    }

    private function getCategories($key, $value)
    {
        if (!is_array($value)) {
            return false;
        }

        $newValue = '';
        
        foreach ($value as $single) {
            
            if ($newValue != '') {
                $newValue .= ', ';
            }

            $newValue .= $single['name'];
        }

        $data = new PreparedMetaData();
        $data->data = $newValue;
        $data->name = $this->metaPrefix . $key;
        $data->type = 'input';

        return array($data);
    }

    private function getDescription($key, $value)
    {
        if (!is_array($value)) {
            return false;
        }

        $data = array();

        if (isset($value['tags'])) {
            $tagValue = '';

            foreach ($value['tags'] as $tag) {
                if ($tagValue != '') {
                    $tagValue .= ', ';
                }

                $tagValue .= $tag;
            }

            $pData = new PreparedMetaData();
            $pData->name = $this->metaPrefix . $key . '_tags';
            $pData->type = 'input';
            $pData->data = $tagValue;

            $data[] = $pData;
        }

        if (isset($value['captions'])) {
            $captionValue = '';

            foreach ($value['captions'] as $caption) {
                if ($captionValue != '') {
                    $captionValue .= ', ';
                }

                $captionValue .= '"' . $caption['text'] . '"';

                $pData = new PreparedMetaData();
                $pData->name = $this->metaPrefix . $key . '_captions';
                $pData->data = $captionValue;

                $data[] = $pData;
            }
        }

        return $data;

    }

    private function getAdult($key, $value)
    {
        if (!is_array($value)) {
            return false;
        }

        $data = array();

        foreach ($value as $itemKey => $item) {
            $pData = new PreparedMetaData();
            $pData->name = $this->metaPrefix . $key . '_' . $itemKey;
            $pData->data = $item;

            if (is_bool($item)) {
                $pData->data = ($item) ? 'true' : 'false';
            }

            $data[] = $pData;
        }

        return $data;
    }

    private function getTags($key, $value)
    {
        if (!is_array($value)) {
            return false;
        }

        $newValue = '';

        foreach ($value as $item) {
            if ($newValue != '') {
                $newValue .= ', ';
            }

            $newValue .= $item['name'];
        }

        $pData = new PreparedMetaData();
        $pData->name = $this->metaPrefix . $key;
        $pData->data = $newValue;

        return array($pData);
    }

    private function getImageType($key, $value)
    {
        if (!is_array($value)) {
            return false;
        }

        $data = array();

        if (isset($value['clipArtType'])) {
            $pData = new PreparedMetaData();
            $pData->name = $this->metaPrefix . $key . '_clipArtType';

            switch ($value['clipArtType']) {
                case 0:
                    $newValue = 'non-clipart';
                    break;

                case 1:
                    $newValue = 'ambiguous';
                    break;

                case 2:
                    $newValue = 'normal-clipart';
                    break;

                case 3:
                    $newValue = 'good-clipart';
                    break;

                default:
                    $newValue = 'unknown';
            }

            $pData->data = $newValue;
            $data[] = $pData;
        }

        if (isset($value['lineDrawingType'])) {
            $pData = new PreparedMetaData();
            $pData->name = $this->metaPrefix . $key . '_lineDrawingType';
            $pData->data = ($value['lineDrawingType'] === 0) ? 'Non-LineDrawing' : 'LineDrawing';
            $data[] = $pData;
        }

        return $data;
    }

    private function getColor($key, $value)
    {
        if (!is_array($value)) {
            return false;
        }

        $data = array();

        foreach ($value as $itemKey => $item) {
            $pData = new PreparedMetaData();
            $pData->name = $this->metaPrefix . $key . '_' . $itemKey;

            if (is_array($item)) {
                $pData->data = implode(', ', $item);
            } elseif (is_bool($item)) {
                $pData->data = ($item) ? 'true' : 'false';
            } else {
                $pData->data = $item;
            }

            $data[] = $pData;
        }

        return $data;
    }

    private function getFaces($key, $value)
    {
        if (!is_array($value)) {
            return false;
        }

        $data = array();
        $faceNo = 0;

        foreach ($value as $faceData) {
            $faceNo++;

            foreach ($faceData as $itemKey => $item) {

                if (is_array($item)) {

                    foreach ($item as $k => $v) {
                        $pData = new PreparedMetaData();
                        $pData->name = $this->metaPrefix . $key . '_' . $faceNo . '_' . $itemKey . '_' . $k;
                        $pData->data = $v;

                        $data[] = $pData;
                    }

                } else {
                    $pData = new PreparedMetaData();
                    $pData->name = $this->metaPrefix . $key . '_' . $faceNo . '_' . $itemKey;
                    $pData->data = $item;

                    $data[] = $pData;
                }
            }
        }

        if (!empty($data)) {
            $pData = new PreparedMetaData();
            $pData->name = $this->metaPrefix . $key . '_hasFaces';
            $pData->data = 'hasFaces';

            $data[] = $pData;
        }

        return $data;
    }
}