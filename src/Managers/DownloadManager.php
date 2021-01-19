<?php

namespace Lanfest\WebDriverBinaryDownloader\Managers;

use Composer\Plugin\PluginInterface;
use Composer\Util\SyncHelper;
use Lanfest\WebDriverBinaryDownloader\Interfaces\ConfigInterface;

class DownloadManager
{
    /**
     * @var \Composer\Package\CompletePackage
     */
    private $ownerPackage;

    /**
     * @var \Composer\Downloader\DownloadManager
     */
    private $downloadManager;

    /**
     * @var \Composer\Installer\InstallationManager
     */
    private $installationManager;

    /**
     * @var \Composer\Cache
     */
    private $cacheManager;

    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Factories\DriverPackageFactory
     */
    private $driverPkgFactory;

    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Interfaces\ConfigInterface
     */
    private $pluginConfig;

    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Analysers\PlatformAnalyser
     */
    private $platformAnalyser;

    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Utils\SystemUtils
     */
    private $systemUtils;

    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Utils\DataUtils
     */
    private $dataUtils;

    /**
     * @var \Lanfest\WebDriverBinaryDownloader\Utils\StringUtils
     */
    private $stringUtils;

    /**
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * @param \Composer\Package\CompletePackage $ownerPackage
     * @param \Composer\Downloader\DownloadManager $downloadManager
     * @param \Composer\Installer\InstallationManager $installationManager
     * @param \Composer\Cache $cacheManager
     * @param \Lanfest\WebDriverBinaryDownloader\Factories\DriverPackageFactory $driverPkgFactory
     * @param \Lanfest\WebDriverBinaryDownloader\Interfaces\ConfigInterface $pluginConfig
     */
    public function __construct(
        \Composer\Package\CompletePackage $ownerPackage,
        \Composer\Downloader\DownloadManager $downloadManager,
        \Composer\Installer\InstallationManager $installationManager,
        \Composer\Cache $cacheManager,
        \Lanfest\WebDriverBinaryDownloader\Factories\DriverPackageFactory $driverPkgFactory,
        \Lanfest\WebDriverBinaryDownloader\Interfaces\ConfigInterface $pluginConfig,
        \Composer\Composer $composer
    ) {
        $this->ownerPackage = $ownerPackage;
        $this->downloadManager = $downloadManager;
        $this->installationManager = $installationManager;
        $this->cacheManager = $cacheManager;
        $this->driverPkgFactory = $driverPkgFactory;
        $this->pluginConfig = $pluginConfig;
        $this->composer = $composer;

        $this->platformAnalyser = new \Lanfest\WebDriverBinaryDownloader\Analysers\PlatformAnalyser();
        $this->systemUtils = new \Lanfest\WebDriverBinaryDownloader\Utils\SystemUtils();
        $this->dataUtils = new \Lanfest\WebDriverBinaryDownloader\Utils\DataUtils();
        $this->stringUtils = new \Lanfest\WebDriverBinaryDownloader\Utils\StringUtils();
    }

    public function downloadRelease(array $versions)
    {
        $executableName = $this->dataUtils->extractValue(
            $this->pluginConfig->getExecutableFileNames(),
            $this->platformAnalyser->getPlatformCode(),
            ''
        );

        $ownerName = $this->ownerPackage->getName();

        if (!$executableName) {
            $platformName = $this->platformAnalyser->getPlatformName();

            throw new \Lanfest\WebDriverBinaryDownloader\Exceptions\PlatformNotSupportedException(
                sprintf('The package %s does not support platform: %s', $ownerName, $platformName)
            );
        }

        $name = sprintf('%s-virtual', $ownerName);

        $relativePath = $this->systemUtils->composePath(
            $this->ownerPackage->getName(),
            'downloads'
        );

        $fullPath = $this->systemUtils->composePath(
            $this->installationManager->getInstallPath($this->ownerPackage),
            'downloads'
        );

        while ($version = array_shift($versions)) {
            $package = $this->driverPkgFactory->create(
                $name,
                $this->getDownloadUrl($version),
                $version,
                $relativePath,
                array($executableName)
            );

            if (!$this->downloadAndInstallPackageSync($package, $fullPath)) {
                continue;
            }

            return $package;
        }

        throw new \Exception('Failed to download requested driver');
    }

    private function getDownloadUrl($version)
    {
        $requestConfig = $this->pluginConfig->getRequestUrlConfig();

        $platformCode = $this->platformAnalyser->getPlatformCode();

        $remoteFiles = $this->pluginConfig->getRemoteFileNames();

        if (!isset($remoteFiles[$platformCode])) {
            throw new \Exception('Failed to resolve a file for the platform. Download driver manually');
        }

        $driverHashes = $this->pluginConfig->getDriverVersionHashMap();

        $fileHash = $this->dataUtils->extractValue($driverHashes, $version, '');

        $variables = array(
            'version' => $version,
            'hash' => $fileHash,
            'major' => $this->stringUtils->strTokOffset($version, 1),
            'major-minor' => $this->stringUtils->strTokOffset($version, 2)
        );

        $fileName = $this->stringUtils->stringFromTemplate(
            $remoteFiles[$platformCode],
            $variables
        );

        return $this->stringUtils->stringFromTemplate(
            $requestConfig[ConfigInterface::REQUEST_DOWNLOAD],
            array_replace($variables, array('file' => $fileName))
        );
    }

    /**
     * Downloads specific package into specific folder.
     *
     * @param \Composer\Package\PackageInterface $package
     * @param string $path
     *
     * @return bool
     */
    private function downloadAndInstallPackageSync(
      \Composer\Package\PackageInterface $package,
      string $path
    ) {
        $composerVersion = PluginInterface::PLUGIN_API_VERSION;
        if (version_compare($composerVersion, '2.0.0', '>=')) {
            return $this->downloadAndInstallPackageSyncComposer2($package, $path);
        }

        return $this->downloadAndInstallPackageSyncComposer1($package, $path);
    }

    /**
     * Downloads specific package into specific folder (Composer 1).
     *
     * @param \Composer\Package\PackageInterface $package
     * @param string $path
     *
     * @return bool
     */
    private function downloadAndInstallPackageSyncComposer1(
      \Composer\Package\PackageInterface $package,
      string $path
    ) {
        $downloader = $this->downloadManager->getDownloaderForInstalledPackage($package);

        if ($downloader === null) {
            return false;
        }

        /**
         * Some downloader types have the option to mute the output,
         * which is why there is the third call argument (not present
         * in interface footprint).
         */
        $downloader->download($package, $path, false);

        return true;
    }

    /**
     * Downloads specific package into specific folder (Composer 2).
     *
     * @param \Composer\Package\PackageInterface $package
     * @param string $path
     *
     * @return bool
     */
    private function downloadAndInstallPackageSyncComposer2(
      \Composer\Package\PackageInterface $package,
      string $path
    ) {
        SyncHelper::downloadAndInstallPackageSync(
            $this->composer->getLoop(),
            $this->downloadManager->getDownloader('zip'),
            $path,
            $package
        );

        return true;
    }

}
