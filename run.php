#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$application = new \UAM\Application('Unofficial sdk for cloud.mail.ru', '1.1');
$application->run();
