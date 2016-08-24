<?php

/**
 * Created by PhpStorm.
 * User: mona
 * Date: 18/08/16
 * Time: 12:22
 */

use Pimcore\Controller\Action\Admin;

class ComputerVision_AdminController extends Admin
{
    public function getDataAction()
    {
        $this->disableViewAutoRender();

        $assetId = $this->getParam("id");
        $type = $this->getParam("type");

        try {
            $manager = new \ComputerVision\Manager($assetId, $type);
            $manager->getData();

            $this->_helper->json([
                "success" => true
            ]);

        } catch (\Exception $e) {
            \Logger::err($e->getMessage());

            $this->_helper->json([
                "success" => false,
                "message" => $e->getMessage()
            ]);
        }
    }
}
