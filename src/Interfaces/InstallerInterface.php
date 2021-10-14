<?php

namespace WebdriverBinary\WebDriverBinaryDownloader\Interfaces;

use WebdriverBinary\WebDriverBinaryDownloader\Interfaces\ConfigInterface as Config;

interface InstallerInterface
{
    /**
     * @param Config $pluginConfig
     */
    public function executeWithConfig(Config $pluginConfig);
}
