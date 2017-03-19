<?php

namespace Smalot\Vagrant\RestBundle\Model;

/**
 * Class System
 * @package Smalot\Vagrant\RestBundle\Model
 */
class System
{
    /**
     * System constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return array
     */
    public function getMemory()
    {
        $info = [];
        $output = shell_exec('free -b');

        if (preg_match(
          '/^.*?[\n\r]+'.
          '.*?:\s*([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)[\r\n]+'.
          '.*?:\s*([0-9]+)\s+([0-9]+)\s+([0-9]+).*$/mis',
          $output,
          $match
        )) {
            $info['memory'] = [
              'total' => intval($match[1]),
              'used' => intval($match[2]),
              'free' => intval($match[3]),
              'shared' => intval($match[4]),
              'buffer' => intval($match[5]),
              'available' => intval($match[6]),
            ];

            $info['swap'] = [
              'total' => intval($match[7]),
              'used' => intval($match[8]),
              'free' => intval($match[9]),
            ];
        }

        return $info;
    }

    /**
     * @return array
     */
    public function getCpu()
    {
        $cpu = [];
        $cpuInfo = @file_get_contents('/proc/cpuinfo');

        if (preg_match_all('/^cpu MHz\s*:\s*([0-9\.]+)$/mi', $cpuInfo, $match)) {
            $usage = $this->getCpuUsage(200000);

            foreach ($match[1] as $id => $cur) {
                $cpu[$id] = $this->getCpuDetails($id);
                $cpu[$id]['freq_cur'] = floatval($cur);
                $cpu[$id]['usage'] = $usage[$id];
            }
        }

        return ['cpu' => $cpu];
    }

    /**
     * @param int $id
     * @return array
     */
    protected function getCpuDetails($id)
    {
        $min = file_get_contents('/sys/devices/system/cpu/cpu'.$id.'/cpufreq/cpuinfo_min_freq');
        $max = file_get_contents('/sys/devices/system/cpu/cpu'.$id.'/cpufreq/cpuinfo_max_freq');

        $details = [
          'freq_min' => floatval($min),
          'freq_max' => floatval($max),
        ];

        return $details;
    }

    /**
     * @param int $duration
     * @return array
     */
    protected function getCpuUsage($duration)
    {
        $cpu = [];

        $usage_before = $this->readCpuUsage();
        usleep($duration);
        $usage_after = $this->readCpuUsage();

        foreach ($usage_before as $id => $usage) {
            $idleDiff = $usage_after[$id]['idle'] - $usage_before[$id]['idle'];
            $totalDiff = $usage_after[$id]['total'] - $usage_before[$id]['total'];
            $cpu[$id] = 100 - ($idleDiff / $totalDiff * 100);
        }

        return $cpu;
    }

    /**
     * @return array
     */
    protected function readCpuUsage()
    {
        $stat = file_get_contents('/proc/stat');
        $cpus = [];

        if (preg_match_all(
          '/^cpu([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)/mi',
          $stat,
          $match
        )) {
            foreach ($match[1] as $pos => $id) {
                $total = 0;

                for ($i = 2; $i < count($match); $i++) {
                    $total += $match[$i][$pos];
                }

                $cpus[$id] = [
                  'idle' => floatval($match[5][$pos]),
                  'total' => $total,
                ];
            }
        }

        return $cpus;
    }
}
