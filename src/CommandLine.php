<?php

/**
 * Class CommandLine
 * Receives CLI input and decides which command must run
 */
class CommandLine
{
    public static $debug = false;

    public static $colorTags = [
        "/<red>(.*)?<\/red>/",
        "/<yellow>(.*)?<\/yellow>/",
        "/<green>(.*)?<\/green>/",
    ];
    public static $colorReplacements = [
        "\033[31m$1\033[0m",
        "\033[33m$1\033[0m",
        "\033[32m$1\033[0m",
    ];

    /***
     * Parses the received command && decides which command must run
     * @param array $arguments
     */
    public static function init($arguments)
    {
        // check if ocomposer.json exists
        try {
            new OxideComposer();
        } catch (Exception $e) {
            if ($e->getCode() === 1) {
                self::setup();
                return;
            }
        }

        if (!isset($arguments[1])) {
            $arguments[1] = "help";
        }

        self::parseOptions($arguments);

        switch ($arguments[1]) {
            case "install":
                self::install($arguments[2]);
                break;
            case "update":
                self::update();
                break;
            case "about":
                self::about();
                break;
            case "validate":
                self::validate();
                break;
            case "full-update":
                self::fullUpdate();
                break;
            default:
                self::help();
                break;
        }
    }

    /**
     * Checks for options in arguments list
     * @param array $arguments
     */
    public static function parseOptions(&$arguments)
    {
        $firstChar = substr($arguments[1], 0, 1);

        if ($firstChar == "-") {
            $option = substr($arguments[1], 1);

            switch ($option) {
                case "d":
                    self::$debug = true;
                    break;
            }
            // delete the option from arguments list
            unset($arguments[1]);
            $arguments = array_values($arguments);
        }
    }

    /**
     * Runs validate command
     */
    public static function validate()
    {
        // OComposer construct already Validates them
        try {
            new OxideComposer();
        } catch (Exception $e) {
            self::handleError($e);
        }

        self::display("<green>They seem valid</green>");
    }

    /**
     * Runs install command
     * @param string $pluginId Ex: kits.668
     */
    public static function install($pluginId)
    {
        try {
            $composer = new OxideComposer();
            $composer->install($pluginId);
        } catch (Exception $e) {
            self::handleError($e);
        }
    }

    public static function handleError($e)
    {
        if (self::$debug) {
            echo $e->__toString();
        } else {
            self::display("<red>" . $e->getMessage() . "</red>");
        }
        exit;
    }

    /**
     * Runs update command
     */
    public static function update()
    {
        try {
            $composer = new OxideComposer();
            $composer->update();
        } catch (Exception $e) {
            self::handleError($e);
        }
    }

    /**
     * Runs full update command
     */
    public static function fullUpdate()
    {
        try {
            $composer = new OxideComposer();

            self::display("Stopping server");
            echo self::runCommand("cd .. && ./rustserver stop", true);

            self::display("\n<yellow>Updating Oxide plugins</yellow>");
            $composer->update();

            self::display("<yellow>Updating Rust (will take a long time..)</yellow>");
            $composer->updateRust();

            self::display("\n<yellow>Updating Oxide (may take a while)</yellow>");
            $composer->updateOxide();

            self::display("<yellow>Starting server</yellow>");
            echo self::runCommand("cd .. && ./rustserver start", true);
        } catch (Exception $e) {
            self::handleError($e);
        }
    }

    /**
     * Runs about command
     */
    public static function about()
    {
        self::display("<yellow>Ocomposer is an oxide plugin manager for your servers.</yellow>\n<yellow>See https://github.com/tetreum/ocomposer for more information.</yellow>");
    }

    /**
     * Colors & prints the given text
     * @param string $text
     */
    public static function display($text, $newline = true)
    {
        Utils::p(preg_replace(self::$colorTags, self::$colorReplacements, $text), $newline);
    }

    public static function setup()
    {
        self::display("Config file (ocomposer.json) not found, starting setup....");

        $paths = self::runCommand("find / -name \"oxide\" -type d");
        if (empty($paths) || sizeof($paths) == 0) {
            self::display("<red>Unable to find any /oxide/ folder in your server.</red>");
            return;
        }

        $setupData = new stdClass();
        $setupData->plugins = new stdClass();
        $setupData->login = new stdClass();

        self::display("<yellow>Ocomposer needs an OxideMod.org account to download the plugins, your login details will be saved in ocomposer.json.</yellow>");

        self::prompt("Username: ", function ($response) use (&$setupData) {
            $setupData->login->user = $response;
        });

        self::prompt("Password: ", function ($response) use (&$setupData) {
            $setupData->login->password = $response;
        });

        self::display("<yellow>Select your server /serverfiles/ folder:</yellow>");
        $plainList = "";

        foreach ($paths as $k => $v) {
            $plainList .= "[$k] - $v\n";
        }
        self::display($plainList);

        self::prompt("Input the number: ", function ($response) use (&$setupData, $paths) {
            $number = (int)$response;

            if ($number > sizeof($paths) || $number < 0) {
                $number = 0;
            }

            $setupData->oxideFolder = $paths[$number] . "/";
        });

        $saved = file_put_contents("ocomposer.json", json_encode($setupData, JSON_PRETTY_PRINT));

        if ($saved === false) {
            self::display("<red>Could not save ocomposer.json</red>");
        } else {
            self::display("<green>Config saved</green>");
        }
    }

    /**
     * Prompts a message requesting user input
     * @param string $message
     * @param $callback
     * @param boolean $newline
     */
    public static function prompt($message, $callback, $newline = false)
    {
        self::display($message, $newline);

        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);

        $callback(trim($line));

        fclose($handle);
    }

    /**
     * Runs a terminal command
     * @param string $command
     * @param bool $stringOutput force string output
     * @return mixed
     */
    public static function runCommand($command, $stringOutput = false)
    {
        exec($command, $out);

        if ($stringOutput && is_array($out)) {
            $out = implode("\n", $out);
        }

        return $out;
    }


    /**
     * Runs help command
     */
    public static function help()
    {
        self::display("
  ____       _     _       _____
 / __ \     (_)   | |     / ____|
| |  | |_  ___  __| | ___| |     ___  _ __ ___  _ __   ___  ___  ___ _ __
| |  | \ \/ / |/ _` |/ _ \ |    / _ \| '_ ` _ \| '_ \ / _ \/ __|/ _ \ '__|
| |__| |>  <| | (_| |  __/ |___| (_) | | | | | | |_) | (_) \__ \  __/ |
 \____//_/\_\_|\__,_|\___|\_____\___/|_| |_| |_| .__/ \___/|___/\___|_|
 					       | |
 					       |_|
<green>OxideComposer</green> version <yellow>0.3</yellow> 2016-03-25

<yellow>Usage:</yellow>
	[options] command [arguments]

<yellow>Options:</yellow>
	<green>-d</green>		Prints Exception traces

<yellow>Available commands:</yellow>
	<green>about</green>		Short information about OxideComposer
	<green>install</green>		Installs the plugin set as argument1 Ex: 'ocomposer.phar install kits.668'
	<green>update</green>		Updates your plugins to the latest version according to ocomposer.json & installed.json
	<green>validate</green>        Validates ocomposer.json & installed.json
		");
    }
}
