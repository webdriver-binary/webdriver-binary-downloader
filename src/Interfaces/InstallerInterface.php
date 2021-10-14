<?php

namespace WebDriverBinaryDownloader\Interfaces;

use WebDriverBinaryDownloader\Interfaces\ConfigInterface as Config;

interface InstallerInterface
{
    /**
     * @param Config $pluginConfig
     */
    public function executeWithConfig(Config $pluginConfig);
}
