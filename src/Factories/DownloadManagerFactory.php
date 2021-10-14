<?php

namespace WebdriverBinary\WebDriverBinaryDownloader\Factories;

use WebdriverBinary\WebDriverBinaryDownloader\Interfaces\ConfigInterface;

class DownloadManagerFactory
{
    /**
     * @var \WebdriverBinary\WebDriverBinaryDownloader\Composer\Context
     */
    private $composerContext;

    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @var \Composer\IO\IOInterface
     */
    private $cliIO;

    /**
     * @var \WebdriverBinary\WebDriverBinaryDownloader\Utils\SystemUtils
     */
    private $systemUtils;

    /**
     * @param \WebdriverBinary\WebDriverBinaryDownloader\Composer\Context $composerContext
     */
    public function __construct(
        \WebdriverBinary\WebDriverBinaryDownloader\Composer\Context $composerContext,
        \Composer\IO\IOInterface $cliIO,
        \Composer\Composer $composer
    ) {
        $this->composerContext = $composerContext;
        $this->cliIO = $cliIO;
        $this->composer = $composer;

        $this->systemUtils = new \WebdriverBinary\WebDriverBinaryDownloader\Utils\SystemUtils();
    }

    public function create(ConfigInterface $pluginConfig)
    {
        $composer = $this->composerContext->getLocalComposer();
        $packages = $this->composerContext->getActivePackages();
        $packageResolver = new \WebdriverBinary\WebDriverBinaryDownloader\Resolvers\PackageResolver(
            array($composer->getPackage())
        );
        $pluginPackage = $packageResolver->resolveForNamespace(
            $packages,
            get_class($pluginConfig)
        );

        return new \WebdriverBinary\WebDriverBinaryDownloader\Managers\DownloadManager(
            $pluginPackage,
            $composer->getDownloadManager(),
            $composer->getInstallationManager(),
            $this->createCacheManager($composer, $pluginPackage->getName()),
            new \WebdriverBinary\WebDriverBinaryDownloader\Factories\DriverPackageFactory(),
            $pluginConfig,
            $this->composer
        );
    }

    private function createCacheManager(\Composer\Composer $composer, $cacheName)
    {
        $composerConfig = $composer->getConfig();

        $cacheDir = $composerConfig->get('cache-dir');

        return new \Composer\Cache(
            $this->cliIO,
            $this->systemUtils->composePath($cacheDir, 'files', $cacheName, 'downloads')
        );
    }
}
