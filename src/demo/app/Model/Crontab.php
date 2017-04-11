<?php
namespace Model;

abstract class Crontab extends \Rsf\Crontab {

    public function __construct($name = null) {
        if ($name) {
            $this->name = $name;
        }
        $this->setContextHandler(\Rsf\Cacher::factory('redis'));
    }
}
