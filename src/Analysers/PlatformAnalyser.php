<?php

namespace WebDriverBinaryDownloader\Analysers;

class PlatformAnalyser implements \WebDriverBinaryDownloader\Interfaces\PlatformAnalyserInterface
{
    public function getPlatformCode()
    {
        if (stripos(PHP_OS, 'win') === 0) {
            if (PHP_INT_SIZE === 8) {
                return self::TYPE_WIN64;
            }

            return self::TYPE_WIN32;
        }
        
        if (stripos(PHP_OS, 'darwin') === 0) {
            return self::TYPE_MAC64;
        }
        
        if (stripos(PHP_OS, 'linux') === 0) {
            if (PHP_INT_SIZE === 8) {
                return self::TYPE_LINUX64;
            }

            return self::TYPE_LINUX32;
        }

        throw new \Exception('Platform code detection failed');
    }

    public function getPlatformName()
    {
        $names = array(
            self::TYPE_LINUX32 => 'Linux 32Bits',
            self::TYPE_LINUX64 => 'Linux 64Bits',
            self::TYPE_MAC64 => 'Mac OS X',
            self::TYPE_WIN32 => 'Windows 32Bits',
            self::TYPE_WIN64 => 'Windows 64Bits'
        );
        
        return $names[$this->getPlatformCode()];
    }
}
