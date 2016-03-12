<?php

/******************************
***   OComposer bootstrap   ***
*******************************/

$files = [
    "Utils",
    "CommandLine",
    "Oxide",
    "OxideComposer",
];

foreach ($files as $file) {
    require "$file.php";
}

CommandLine::init($argv);
exec("chmod +x $endFile");
echo "PHAR created\n";
