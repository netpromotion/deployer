<?php

require_once __DIR__ . "/init.php";

$config = (new \Netpromotion\Deployer\Deployer(getcwd()))->getConfig();

if (isset($config["log"]["config"])) {
    file_put_contents($config["log"]["config"], var_export($config, true));
}
$config["log"] = $config["log"]["output"];

if (null === $config["log"]) {
    unset($config["log"]);
}

return $config;
