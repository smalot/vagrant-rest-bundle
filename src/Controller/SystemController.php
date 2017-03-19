<?php

namespace Smalot\Vagrant\RestBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Smalot\Vagrant\RestBundle\Model\System;
use Smalot\Vagrant\Wrapper\Vagrant;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Process\Process;

/**
 * Class SystemController
 * @package Smalot\Vagrant\RestBundle\Controller
 * @Route("/system")
 */
class SystemController extends FOSRestController
{
    /**
     * SystemController constructor.
     */
    public function __construct()
    {
    }

    /**
     * @View()
     * @Get()
     */
    public function infoAction()
    {
        $info = [];

        /** @var System $system */
        $system = $this->get('vagrant_rest.model.system');

        $info += $system->getMemory();
        $info += $system->getCpu();

        return $info;
    }
}
