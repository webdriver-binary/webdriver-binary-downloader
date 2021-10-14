<?php

namespace WebdriverBinary\WebDriverBinaryDownloader\Analysers;

use WebdriverBinary\WebDriverBinaryDownloader\Composer\Config as ComposerConfig;

class PackageAnalyser
{
    /**
     * @var \WebdriverBinary\WebDriverBinaryDownloader\Utils\DataUtils
     */
    private $dataUtils;
    
    public function __construct()
    {
        $this->dataUtils = new \WebdriverBinary\WebDriverBinaryDownloader\Utils\DataUtils();
    }

    public function isPluginPackage(\Composer\Package\PackageInterface $package)
    {
        return $package->getType() === ComposerConfig::COMPOSER_PLUGIN_TYPE;
    }

    public function ownsNamespace(\Composer\Package\PackageInterface $package, $namespace)
    {
        $autoloadConfig = $package->getAutoload();

        $pathMapping = $this->dataUtils->extractValue(
            $autoloadConfig,
            ComposerConfig::PSR4_CONFIG,
            array()
        );

        return (bool)array_filter(
            array_keys($pathMapping),
            function ($item) use ($namespace) {
                return strpos($namespace, rtrim($item, '\\')) === 0;
            }
        );
    }
}
