<?php

namespace WebdriverBinary\WebDriverBinaryDownloader\Analysers;

class EnvironmentAnalyser
{
    /**
     * @var \WebdriverBinary\WebDriverBinaryDownloader\Interfaces\ConfigInterface
     */
    private $pluginConfig;
    
    /**
     * @var \WebdriverBinary\WebDriverBinaryDownloader\Analysers\PlatformAnalyser
     */
    private $platformAnalyser;
    
    /**
     * @var \WebdriverBinary\WebDriverBinaryDownloader\Resolvers\VersionResolver
     */
    private $versionResolver;

    /**
     * @param \WebdriverBinary\WebDriverBinaryDownloader\Interfaces\ConfigInterface $pluginConfig
     * @param \Composer\IO\IOInterface $cliIO
     */
    public function __construct(
        \WebdriverBinary\WebDriverBinaryDownloader\Interfaces\ConfigInterface $pluginConfig,
        \Composer\IO\IOInterface $cliIO = null
    ) {
        $this->pluginConfig = $pluginConfig;
        
        $this->platformAnalyser = new \WebdriverBinary\WebDriverBinaryDownloader\Analysers\PlatformAnalyser();
        $this->versionResolver = new \WebdriverBinary\WebDriverBinaryDownloader\Resolvers\VersionResolver($cliIO);
    }

    public function resolveBrowserVersion()
    {
        $platformCode = $this->platformAnalyser->getPlatformCode();
        $binaryPaths = $this->pluginConfig->getBrowserBinaryPaths();

        if (!isset($binaryPaths[$platformCode])) {
            return '';
        }

        return $this->versionResolver->pollForExecutableVersion(
            $binaryPaths[$platformCode],
            $this->pluginConfig->getBrowserVersionPollingConfig()
        );
    }
}
