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
 * Class MachineController
 * @package Smalot\Vagrant\RestBundle\Controller
 * @Route("/vm")
 */
class MachineController extends FOSRestController
{
    /**
     * @var Vagrant
     */
    protected $vagrant;

    protected $lastOutput;

    /**
     * MachineController constructor.
     */
    public function __construct()
    {
        $env = [
          'HOME' => '/home/sebastien',
          'PATH' => '/usr/local/bin:/usr/local/sbin:/bin:/usr/bin:/usr/sbin',
        ];
        $this->vagrant = new Vagrant('/data/vagrant/elis', $env);
    }

    /**
     * @View()
     * @Get()
     */
    public function listAction()
    {
        $status = $this->vagrant->getGlobalStatus();

        return array_values($status);
    }

    /**
     * @View()
     * @Get("/info")
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

    /**
     * @View()
     * @Get("/up")
     */
    public function runAction()
    {
        $vagrant = $this->vagrant;

        $this->lastOutput = '';

        $process = $vagrant->doUp();
        $process->start(
          function ($type, $output) {
              $this->lastOutput .= $output;
          }
        );

        $this->get('event_dispatcher')->addListener(
          KernelEvents::TERMINATE,
          function () use ($process) {
              try {
                  /** @var Process $process */
                  $process->wait();
                  $output = $process->getOutput();
                  echo '';
              } catch (\Exception $e) {
                  echo '';
              }

              sleep(5);

              echo 'done';
          }
        );

        $headers = [
          'Connection' => 'close',
        ];

        return array('code' => 'ok', 'message' => 'machine launch');
    }
}
