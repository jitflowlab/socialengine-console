<?php

namespace SocialEngine\Console\Commands;

use SocialEngine\Console\Command;
use SocialEngine\Console\Helper\Packages;

/**
 * SE Builder
 */
class Build extends Command
{
    /**
     * @cli-command build:packages
     * @cli-info Build all SE packages
     */
    public function packages()
    {
        $packages = new Packages($this);

        foreach ($packages->getJsonFiles() as $file) {
            unlink($file->getRealPath());
        }

        $scaffold = APPLICATION_PATH . 'temporary/scaffold/index.html';
        if (!is_file($scaffold)) {
            touch($scaffold);
        }

        file_put_contents($this->getConfig('path') . 'application/packages/index.html', '');

        $this->step('Building package JSON', function () use ($packages) {
            $write = function ($manifestPath) use ($packages) {
                try {
                    $package = $packages->buildPackageFile($manifestPath);
                    if ($package) {
                        $packageFileName = $this->getConfig('path');
                        $packageFileName .= 'application/packages/' . $package->getKey() . '.json';
                        file_put_contents($packageFileName, json_encode($package->toArray(), JSON_PRETTY_PRINT));
                        $this->write(' -> ' . str_replace($this->getConfig('path'), '', $packageFileName));
                    }
                } catch (\Exception $e) {
                    $this->color('yellow')->write($e->getMessage());
                }
            };

            foreach ($packages->getStructure() as $type => $info) {
                if (in_array($type, $packages->getActions())) {
                    $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, trim($info['path'], '/\\'));
                    $path = $this->getConfig('path') . $path;

                    if (!$info['array']) {
                        $manifest = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, trim($info['manifest']));
                        $manifestPath = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $manifest;
                        $write($manifestPath);
                    } else {
                        $dirs = scandir($path);
                        foreach ($dirs as $dir) {
                            $dirPath = $path . DIRECTORY_SEPARATOR . $dir;

                            if ($dir[0] == '.' || !is_dir($dirPath)) {
                                continue;
                            }

                            $manifest = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, trim($info['manifest']));
                            $manifestPath = $dirPath . DIRECTORY_SEPARATOR . $manifest;
                            $write($manifestPath);
                        }
                    }
                }
            }
        });

        $this->step('Build package DB', function () use ($packages) {
            $packages->buildPackageDb();
        });
    }
}
