<?php

echo "<pre>";

echo "PHP: ";
var_dump(PHP_BINARY);

echo "\nINI: ";
var_dump(php_ini_loaded_file());

echo "\nPHPRC: ";
var_dump(getenv("PHPRC"));

echo "\nCURL: ";
var_dump(function_exists("curl_init"));

echo "\nINI SCAN: ";
var_dump(php_ini_scanned_files());

echo "</pre>";