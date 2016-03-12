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
	public static function init ($arguments)
	{
        if (!isset($arguments[1])) {
            $arguments[1] = "help";
        }

		self::parseOptions($arguments);

		switch ($arguments[1])
		{
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
			default:
				self::help();
			break;
		}
	}

    /**
     * Checks for options in arguments list
     * @param array $arguments
     */
	public static function parseOptions (&$arguments)
	{
        $firstChar = substr($arguments[1], 0, 1);

		if ($firstChar == "-")
		{
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
	public static function validate ()
	{
        // OComposer construct already Validates them
        try {
            $composer = new OxideComposer();
        } catch (Exception $e)
		{
			if (self::$debug) {
				echo $e->__toString();
			} else {
				self::display("<red>" . $e->getMessage() . "</red>");
			}
			exit;
		}

        self::display("<green>They seem valid</green>");
    }

    /**
     * Runs install command
     * @param string $pluginId Ex: kits.668
     */
	public static function install ($pluginId)
	{
		try {
            $composer = new OxideComposer();
			$composer->install($pluginId);
		} catch (Exception $e)
		{
			if (self::$debug) {
				echo $e->__toString();
			} else {
				self::display("<red>" . $e->getMessage() . "</red>");
			}
			exit;
		}
	}

    /**
     * Runs update command
     */
	public static function update ()
	{
		try {
            $composer = new OxideComposer();
			$composer->update();
		} catch (Exception $e)
		{
			if (self::$debug) {
				echo $e->__toString();
			} else {
				self::display("<red>" . $e->getMessage() . "</red>");
			}
			exit;
		}
	}

    /**
     * Runs about command
     */
	public static function about () {
		self::display("<yellow>Ocomposer is an oxide plugin manager for your servers.</yellow>\n<yellow>See https://github.com/tetreum/ocomposer for more information.</yellow>");
	}

    /**
     * Colors & prints the given text
     * @param string $text
     */
	public static function display ($text)
	{
		Utils::p(preg_replace(self::$colorTags, self::$colorReplacements, $text));
	}

    /**
     * Runs help command
     */
	public static function help ()
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
<green>OxideComposer</green> version <yellow>0.1</yellow> 2016-03-11

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
