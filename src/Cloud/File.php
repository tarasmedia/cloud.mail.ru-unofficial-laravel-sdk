<?php
namespace UAM\Cloud;

class File
{
    /** @var string */
    private $name;

    /** @var int */
    private $size;

    /** @var string */
    private $path;

    public function __construct($name, $size, $path)
    {
        $this->name = $name;
        $this->size = (int)$size;
        $this->path = $path;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function getPath()
    {
        return $this->path;
    }
}
