<?php

class Utils
{
	/**
	 * Checks if $response contains $str
	 * @param string $response
	 * @param string $str
	 * @return bool
	 */
	public static function contains ($response, $str)
	{
		if (strpos($response, $str) !== false) {
			return true;
		}
		return false;
	}

    /**
     * Checks if oxidemod plugin version is greater than the installed one
     * @param string $currentVersion Ex: 3.54.7
     * @param string $ownVersion Ex: 2.3.6
     * @return bool|int Returns 2 if the difference is a major release
     */
	public static function isGreater ($currentVersion, $ownVersion)
	{
		if ($currentVersion == $ownVersion) {
			return false;
		}

		$cVersion = explode(".", $currentVersion);
		$oVersion = explode(".", $ownVersion);
		$totalSteps = sizeof($cVersion) - 1;

		foreach ($cVersion as $k => $number) {
			$oNumber = $oVersion[$k];

			if ($oNumber == "x") {
				$oNumber = 0;
			}

			if ($number < $oNumber) {
				return false;
			} else if ($number > $oNumber) {

				// check if its a major version (as it maybe have breaking changes)
				if ($k != $totalSteps) {
					return 2;
				}
				return 1;
			}
		}
		return false;
	}

    /**
     * Prints a message adapted to cli or browser
     * @param string $message
     */
	public static function p($message)
    {
        if (php_sapi_name() === 'cli') {
  			$message .= "\n";
		} else {
			$message .= "<br>";
		}

		echo $message;
	}
}
