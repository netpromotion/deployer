#!/usr/bin/env php
<?php

require_once __DIR__ . "/init.php";

passthru(sprintf(
    "php %s %s",
    escapeshellarg(VENDOR_DIR . "/bin/deployment"),
    escapeshellarg(__DIR__ . "/configuration_generator.php")
), $return);

return $return;
