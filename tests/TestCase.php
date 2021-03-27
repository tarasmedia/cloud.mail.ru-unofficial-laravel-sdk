<?php
namespace UAM\Tests;

use PHPUnit_Framework_TestCase;

class TestCase extends PHPUnit_Framework_TestCase
{
    protected function playgroundPath($subPath = '')
    {
        return __DIR__ . '/playground/' . $subPath;
    }

    protected function removeDirectory($path)
    {
        if (!is_dir($path)) {
            return;
        }

        $objects = scandir($path);

        foreach ($objects as $object) {
            if ($object != '.' && $object != '..') {
                if (is_dir($path . '/' . $object)) {
                    $this->removeDirectory($path . '/' . $object);
                } else {
                    unlink($path . '/' . $object);
                }
            }
        }

        rmdir($path);
    }

    protected function removeFile($path)
    {
        if (!is_file($path)) {
            return;
        }

        unlink($path);
    }
}
