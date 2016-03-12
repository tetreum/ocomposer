<?php

/**
 * Class Oxide
 * Helper to talk with oxidemod.org
 */
class Oxide
{
	private $domain = "http://oxidemod.org/";
	private $cookies = "/tmp/oxide-cookies.txt";
	public static $fileName;

	const NOT_FOUND_MESSAGE = "The requested plugin could not be found.";
	const INVALID_LOGIN_MESSAGE = 'class="errorPanel"';

	public function __construct ($params = [])
	{
		if (isset($params["cookiesPath"])) {
			$this->cookies = $params["cookiesPath"];
		}
	}

    /**
     * Downloads a plugin, returns plugin name & it's content
     * @param string $id
     * @param int $version
     * @return stdClass
     */
	public function downloadPlugin ($id, $version)
	{
		$download = new stdClass();
		$download->content = $this->query("plugins/" . $id . "/download?version=" . $version, [], true);
		$download->file = Oxide::$fileName;

		Oxide::$fileName = "";

		return $download;
	}

    /**
     * Gets plugin information, like the require data to download it later
     * @param string $id
     * @return stdClass
     * @throws Exception If plugin was not found or failed when parsing the data
     */
	public function getPlugin ($id)
	{
		$response = $this->query("plugins/" . $id);

		if (Utils::contains($response, self::NOT_FOUND_MESSAGE)) {
			throw new Exception("Plugin " . $id . " not found");
		}

		$plugin = new stdClass();
		$plugin->id = $id;

		// get version
		preg_match("/<h3>Version ([0-9.]+)<\/h3>/", $response, $matches);

		if (!isset($matches[1])) {
			throw new Exception("Couldn't get $id version");
		}
		$plugin->version = $matches[1];

		// get download id
		preg_match('/\/download\?version=([0-9]+)/', $response, $matches);

		if (!isset($matches[1])) {
			throw new Exception("Couldn't get $id downloadId");
		}
		$plugin->download = $matches[1];

		return $plugin;
	}

    /**
     * Logins to oxidemod.org
     * @param string $user
     * @param string $password
     * @return bool
     */
	public function login ($user, $password)
	{
		// prevent doing unnecessary logins
		if (file_exists($this->cookies))
		{
			$lastLogin = filemtime($this->cookies);

			if ($lastLogin > strtotime("-3 days")) {
				return true;
			}
		}
		$response = $this->query("login/login", [
			'login' => $user,
			'password' => $password,
			"remember" => 1
		]);

		if (Utils::contains($response, self::INVALID_LOGIN_MESSAGE)) {
			return false;
		}

		return true;
	}

    /**
     * Queries to the page
     * @param string $path
     * @param array $params
     * @param bool $downloadMode If true, search for a filename in curl headers
     * @return mixed
     */
	public function query ($path, $params = [], $downloadMode = false)
	{
		return $this->curl($this->domain . $path, $params, $downloadMode);
	}

    /**
     * Makes the curl request
     * @param string $url
     * @param array $params
     * @param bool $downloadMode If true, search for a filename in curl headers
     * @return mixed
     */
	private function curl($url, $params = [], $downloadMode = false)
	{
		$ch = curl_init($url);
		$header = [];
		$header[0]  = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[]   = "Cache-Control: max-age=0";
		$header[]   = "Connection: keep-alive";
		$header[]   = "Keep-Alive: 300";
		$header[]   = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[]   = "Accept-Language: en-us,en;q=0.5";
		$header[]   = "Pragma: "; // browsers keep this blank.

		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.2; en-US; rv:1.8.1.7) Gecko/20070914 Firefox/2.0.0.7');
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

		if (sizeof($params) > 0)
		{
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

			if (isset($params["login"])) {
				curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookies);
			}

		}
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookies);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_URL, $url);

		if ($downloadMode)
		{
			curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) {
				preg_match('/filename="(.*)?"/', $header, $matches);

				if (isset($matches[1])) {
					Oxide::$fileName = $matches[1];
				}

				return strlen($header);
	   		 });
		}

		return curl_exec($ch);
	}
}
