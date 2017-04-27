<?php

require_once __DIR__ . "/init.php";

$config = (new \Netpromotion\Deployer\Deployer(getcwd()))->getConfig();

file_put_contents(substr($config["log"],0,-3) . "last_run.log", var_export($config, true));

return $config;
