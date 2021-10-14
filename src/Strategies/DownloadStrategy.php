<?php

namespace WebdriverBinary\WebDriverBinaryDownloader\Strategies;

class DownloadStrategy
{
    /**
     * @var \WebdriverBinary\WebDriverBinaryDownloader\Composer\Context
     */
    private $composerContext;

    /**
     * @param \WebdriverBinary\WebDriverBinaryDownloader\Composer\Context $composerContext
     */
    public function __construct(
        \WebdriverBinary\WebDriverBinaryDownloader\Composer\Context $composerContext
    ) {
        $this->composerContext = $composerContext;
    }
    
    public function shouldAllow()
    {
        $composer = $this->composerContext->getLocalComposer();

        $packageResolver = new \WebdriverBinary\WebDriverBinaryDownloader\Resolvers\PackageResolver(
            array($composer->getPackage())
        );

        $repository = $composer->getRepositoryManager()->getLocalRepository();
        $packages = $repository->getCanonicalPackages();

        try {
            $packageResolver->resolveForNamespace($packages, __NAMESPACE__);
        } catch (\WebdriverBinary\WebDriverBinaryDownloader\Exceptions\RuntimeException $exception) {
            return false;
        }

        return true;
    }
}
