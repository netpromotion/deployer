<?php

$vendorDir = __DIR__ . "/../../..";
if (!file_exists($vendorDir . "/autoload.php")) {
    $vendorDir = __DIR__ . "/../vendor";
}

define("VENDOR_DIR", $vendorDir);

/** @noinspection PhpIncludeInspection */
require_once(VENDOR_DIR . "/autoload.php");
