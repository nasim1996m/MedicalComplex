<?php

// Vercel entry point: every request is routed here (see vercel.json) and handed to Laravel.
// Laravel derives its base path from SCRIPT_NAME; leaving it as /api/index.php would make it
// strip "/api" from every URL and break the /api/v1/* routes, so present it as the web root.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../public/index.php';

require __DIR__ . '/../public/index.php';
