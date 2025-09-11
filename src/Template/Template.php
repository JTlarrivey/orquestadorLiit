<?php

namespace App\Template;

class Template
{
    public $name;
    public $path;
    public $config;

    public function __construct($name, $path, $config = [])
    {
        $this->name = $name;
        $this->path = $path;
        $this->config = $config;

        echo "Template loaded: {$name}\n";
    }
}
