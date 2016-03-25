<?php

/**********************************
    Creates the OxideComposer executable
    This compiler is temporal while i check how to work with external files in a PHAR
***********************************/

$endFile = 'compiled/ocomposer';
$content = "#!/usr/bin/env php \n<?php\n";


$files = [
    "Utils",
    "CommandLine",
    "Oxide",
    "OxideComposer",
];

foreach ($files as $file) {
    $content .= str_replace('<?php', '', file_get_contents("src/$file.php")) . "\n";
}

$content .= 'CommandLine::init($argv);';

file_put_contents($endFile, $content);

exec("chmod +x $endFile");
echo "File created\n";
