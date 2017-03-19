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
        $info = [
          'memory' => [
            'total' => 0,
            'used' => 0,
            'free' => 0,
            'shared' => 0,
            'buffer' => 0,
            'available' => 0,
          ],
          'swap' => [
            'total' => 0,
            'used' => 0,
            'free' => 0,
          ],
        ];

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
        $cores = [];
        $cpuinfo = @file_get_contents('/proc/cpuinfo');

        if (preg_match_all('/^cpu MHz\s*:\s*([0-9\.]+)$/mi', $cpuinfo, $match)) {
            $usage = $this->getCpuUsage(500000);

            foreach ($match[1] as $id => $cur) {
                $cores[$id] = $this->getCpuFreqs($id);
                $cores[$id]['freq_cur'] = floatval($cur);
                $cores[$id]['usage'] = $usage[$id];
            }
        }

        $info = $this->getCpuDetails();

        return [
          'cpu' => [
            'info' => $info,
            'cores' => $cores,
          ],
        ];
    }

    /**
     * @return array
     */
    protected function getCpuDetails()
    {
        $details = [
          'architecture' => '',
          'sockets' => 0,
          'core_per_socket' => 0,
          'thread_per_core' => 0,
          'cpu' => 0,
          'model_name' => '',
        ];

        $lscpu = shell_exec('lscpu');

        if (preg_match_all('/^(.*?)\s*:\s*(.*)/m', $lscpu, $match)) {
            $keys = array_map(
              function ($value) {
                  $value = str_replace('(s)', '', $value);

                  return strtolower($value);
              },
              $match[1]
            );
            $values = array_combine($keys, $match[2]);

            $details['architecture'] = $values['architecture'];
            $details['sockets'] = intval($values['socket']);
            $details['core_per_socket'] = intval($values['core per socket']);
            $details['thread_per_core'] = intval($values['thread per core']);
            $details['cpu'] = intval($values['cpu']);
            $details['model_name'] = $values['model name'];
        }

        return $details;
    }

    /**
     * @param int $id
     * @return array
     */
    protected function getCpuFreqs($id)
    {
        $min = @file_get_contents('/sys/devices/system/cpu/cpu'.$id.'/cpufreq/cpuinfo_min_freq');
        $max = @file_get_contents('/sys/devices/system/cpu/cpu'.$id.'/cpufreq/cpuinfo_max_freq');

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
        $cores = [];

        $usage_before = $this->readCpuUsage();
        usleep($duration);
        $usage_after = $this->readCpuUsage();

        foreach ($usage_before as $id => $usage) {
            $idleDiff = $usage_after[$id]['idle'] - $usage_before[$id]['idle'];
            $totalDiff = $usage_after[$id]['total'] - $usage_before[$id]['total'];

            $cores[$id] = ($totalDiff ? 100 - ($idleDiff / $totalDiff * 100) : 0);
        }

        return $cores;
    }

    /**
     * @return array
     */
    protected function readCpuUsage()
    {
        $stat = file_get_contents('/proc/stat');
        $coress = [];

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

                $coress[$id] = [
                  'idle' => floatval($match[5][$pos]),
                  'total' => $total,
                ];
            }
        }

        return $coress;
    }
}
