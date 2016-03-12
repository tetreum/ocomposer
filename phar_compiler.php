<?php

/**********************************
    Creates the OxideComposer PHAR
***********************************/

$endFile = 'ocomposer.phar';
if (!Phar::canWrite()) {
    throw new Exception("phar.readonly must be 0 in php.ini");
}

$p = new Phar($endFile, 0, $endFile);

$p->buildFromDirectory(dirname(__FILE__) . '/src', '/\.php$/');

// set the main file
$defaultStub = $p->createDefaultStub('ocomposer.php');
$stub = "#!/usr/bin/env php \n" . $defaultStub;
$p->setStub($stub);

exec("chmod +x $endFile");
echo "PHAR created\n";
