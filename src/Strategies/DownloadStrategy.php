<?php

namespace WebDriverBinaryDownloader\Strategies;

class DownloadStrategy
{
    /**
     * @var \WebDriverBinaryDownloader\Composer\Context
     */
    private $composerContext;

    /**
     * @param \WebDriverBinaryDownloader\Composer\Context $composerContext
     */
    public function __construct(
        \WebDriverBinaryDownloader\Composer\Context $composerContext
    ) {
        $this->composerContext = $composerContext;
    }
    
    public function shouldAllow()
    {
        $composer = $this->composerContext->getLocalComposer();

        $packageResolver = new \WebDriverBinaryDownloader\Resolvers\PackageResolver(
            array($composer->getPackage())
        );

        $repository = $composer->getRepositoryManager()->getLocalRepository();
        $packages = $repository->getCanonicalPackages();

        try {
            $packageResolver->resolveForNamespace($packages, __NAMESPACE__);
        } catch (\WebDriverBinaryDownloader\Exceptions\RuntimeException $exception) {
            return false;
        }

        return true;
    }
}
