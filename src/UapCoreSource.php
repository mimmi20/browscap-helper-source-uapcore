<?php

namespace BrowscapHelper\Source;

use Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class DirectorySource
 *
 * @author  Thomas Mueller <mimmi20@live.de>
 */
class UapCoreSource implements SourceInterface
{
    /**
     * @param \Monolog\Logger                                   $logger
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param int                                               $limit
     *
     * @return \Generator
     */
    public function getUserAgents(Logger $logger, OutputInterface $output, $limit = 0)
    {
        $counter   = 0;
        $allAgents = [];

        foreach ($this->loadFromPath($output) as $data) {
            if ($limit && $counter >= $limit) {
                return;
            }

            if (empty($data['test_cases'])) {
                continue;
            }

            foreach ($data['test_cases'] as $row) {
                if ($limit && $counter >= $limit) {
                    return;
                }

                if (empty($row['user_agent_string'])) {
                    continue;
                }

                if (array_key_exists($row['user_agent_string'], $allAgents)) {
                    continue;
                }

                yield $row['user_agent_string'];
                $allAgents[$row['user_agent_string']] = 1;
                ++$counter;
            }
        }
    }

    /**
     * @param \Monolog\Logger                                   $logger
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return \Generator
     */
    public function getTests(Logger $logger, OutputInterface $output)
    {
        $allTests = [];

        foreach ($this->loadFromPath($output) as $data) {
            if (empty($data['test_cases'])) {
                continue;
            }

            foreach ($data['test_cases'] as $row) {
                if (empty($row['user_agent_string'])) {
                    continue;
                }

                if (array_key_exists($row['user_agent_string'], $allTests)) {
                    continue;
                }

                $test = [
                    'ua'         => $row['user_agent_string'],
                    'properties' => [
                        'Browser_Name'            => null,
                        'Browser_Type'            => null,
                        'Browser_Bits'            => null,
                        'Browser_Maker'           => null,
                        'Browser_Modus'           => null,
                        'Browser_Version'         => null,
                        'Platform_Codename'       => null,
                        'Platform_Marketingname'  => null,
                        'Platform_Version'        => null,
                        'Platform_Bits'           => null,
                        'Platform_Maker'          => null,
                        'Platform_Brand_Name'     => null,
                        'Device_Name'             => null,
                        'Device_Maker'            => null,
                        'Device_Type'             => null,
                        'Device_Pointing_Method'  => null,
                        'Device_Dual_Orientation' => null,
                        'Device_Code_Name'        => null,
                        'Device_Brand_Name'       => null,
                        'RenderingEngine_Name'    => null,
                        'RenderingEngine_Version' => null,
                        'RenderingEngine_Maker'   => null,
                    ],
                ];

                yield [$row['user_agent_string'] => $test];
                $allTests[$ua] = 1;
            }
        }
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return \Generator
     */
    private function loadFromPath(OutputInterface $output = null)
    {
        $path = 'vendor/thadafinser/uap-core/tests';

        if (!file_exists($path)) {
            return;
        }

        $output->writeln('    reading path ' . $path);

        $iterator = new \RecursiveDirectoryIterator($path);

        foreach (new \RecursiveIteratorIterator($iterator) as $file) {
            /** @var $file \SplFileInfo */
            if (!$file->isFile()) {
                continue;
            }

            $filepath = $file->getPathname();

            $output->write('    reading file ' . str_pad($filepath, 100, ' ', STR_PAD_RIGHT), false);
            switch ($file->getExtension()) {
                case 'yaml':
                    yield Yaml::parse(file_get_contents($filepath));
                    break;
                default:
                    // do nothing here
                    break;
            }
        }
    }
}
