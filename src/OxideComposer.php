<?php

class OxideComposer
{
	private $installedFile = "installed.json";
	private $composerFile = "ocomposer.json";
	private $composerFolder = "composer/";
	private $config;
	private $oxide;
	private $installedPlugins;

	public function __construct ()
	{
		if (!file_exists($this->composerFile)) {
			throw new Exception($this->composerFile . " not found");
		}

		if (!is_dir($this->composerFolder)) {
			$success = mkdir($this->composerFolder);

			if (!$success) {
				throw new Exception("Could not create the required folder " . $this->composerFolder);
			}

			mkdir($this->composerFolder . "backups");
		}

		$this->checkDependencies();

		$this->config = $this->getJson($this->composerFile);

		if (empty($this->config)) {
			throw new Exception("Invalid " . $this->composerFile . " | Fix it in http://json.parser.online.fr");
		}

		$this->installedPlugins = $this->getInstalledPlugins();
	}

    /**
     * Runs a terminal command
     * @param string $command
     * @return mixed
     */
	private function runCommand ($command)
	{
		exec($command, $out);

		return $out;
	}

    /**
     * Cleans downloads folder (used for zipped plugins)
     */
	private function cleanTmpFolder ()
	{
		$tmpFolder = $this->composerFolder . "tmp/";
		if (is_dir($tmpFolder)) {
			rmdir($tmpFolder);
		}
		mkdir($tmpFolder);
	}

    /**
     * Gets installed.json path
     * @return string
     */
	public function getInstalledFilePath () {
		return $this->composerFolder . $this->installedFile;
	}

    /**
     * Gets the backup path based on backup's date
     * @param string $date
     * @return string
     */
	public function getBackupFilePath ($date) {
		return $this->composerFolder . "backups/$date.zip";
	}

    /**
     * Gets the currently installed plugin list
     * @return mixed|stdClass
     * @throws Exception
     */
	public function getInstalledPlugins ()
	{
		$installedJsonPath = $this->getInstalledFilePath();

		if (!file_exists($installedJsonPath)) {
			return new stdClass();
		}

		$plugins = $this->getJson($installedJsonPath);

		if (empty($plugins)) {
			throw new Exception("Invalid $installedJsonPath | Fix it in http://json.parser.online.fr");
		}

		return $plugins;
	}

    /**
     * Saves the list of installed plugins
     * @throws Exception
     */
	public function saveInstalledPlugins ()
	{
		$this->setJson($this->getInstalledFilePath(), $this->installedPlugins);
	}

    /**
     * Gets & decodes a json file
     * @param string $file
     * @param bool $toArray
     * @return mixed
     */
	private function getJson ($file, $toArray = false) {
		return json_decode(file_get_contents($file), $toArray);
	}

    /**
     * Encodes & saves a json file
     * @param string $file
     * @param string $content
     * @throws Exception If save fails
     */
	private function setJson ($file, $content)
	{
		$sucess = file_put_contents($file, json_encode($content, JSON_PRETTY_PRINT));

		if ($sucess === false) {
			throw new Exception("Could not create/update " . $file);
		}
	}

    /**
     * Checks if the server has the required dependencies
     * @throws Exception If a dependency is missing
     */
	private function checkDependencies ()
	{
		if(empty(shell_exec("which zip"))) {
			throw new Exception("Missing required zip dependency");
		}
	}

    /**
     * Saves ocomposer.json file
     * @throws Exception If save fails
     */
	private function saveConfig () {
		$this->setJson($this->composerFile, $this->config);
	}

    /**
     * Starts Oxide helper and logins user
     * @throws Exception If login fails or the required data is missing
     */
	private function initOxide ()
	{
		if (empty($this->oxide))
		{
			if (!isset($this->config->login) || !isset($this->config->login->user) || !isset($this->config->login->password)) {
				throw new Exception("Missing required oxide login params");
			}

			$this->oxide = new Oxide();
			$logged = $this->oxide->login($this->config->login->user, $this->config->login->password);

			if (!$logged) {
				throw new Exception("Login failed");
			}
		}
	}

    /**
     * Installs a plugin
     * @param string $pluginId
     * @throws Exception
     */
	public function install ($pluginId)
	{
		if (isset($this->config->plugins->{$pluginId})) {
			throw new Exception("Plugin already installed");
		}

		$this->config->plugins->{$pluginId} = false;
		$this->initOxide();

		Utils::p("Installing $pluginId");
		$this->installPlugin($this->oxide->getPlugin($pluginId));

		$this->saveConfig();
		$this->saveInstalledPlugins();
		Utils::p("Done");
	}

    /**
     * Checks for installed plugins updates
     * @return bool
     * @throws Exception
     */
	public function update ()
	{
		$this->cleanTmpFolder();
		$this->initOxide();

		/*
			CHECK FOR NEWER VERSIONS
		*/

		$updateList = [];

		foreach ($this->config->plugins as $pluginId => $allowMajorReleases)
		{
			Utils::p("Checking $pluginId");

			$plugin = $this->oxide->getPlugin($pluginId);

			if (!isset($this->installedPlugins->{$pluginId})) {
				$updateList[] = $plugin;
				continue;
			}

			$isGreater = Utils::isGreater($plugin->version, $this->installedPlugins->{$pluginId}->version);

			if (!$isGreater || ($isGreater == 2 && !$allowMajorReleases)) {
				if ($isGreater == 2) {
					Utils::p("  ==> has a major release, not updating");
				}
				continue;
			}

			$updateList[] = $plugin;
		}

		if (sizeof($updateList) == 0) {
			Utils::p("All up to date");
			return true;
		}

		/*
			MAKE A BACKUP
		*/

		$backupFile = $this->getBackupFilePath(date("Y_m_d__H_i_s"));
		Utils::p("================================\n\nCreating backup $backupFile\n\n================================");

		// zip oxide folder, excluding the logs subdirectory
		$out = $this->runCommand("zip -r $backupFile " . $this->config->oxideFolder . ' -x "' . $this->config->oxideFolder . 'logs/*"');

		if (sizeof($out) < 3) {
			throw new Exception("Could not create the backup $backupFile");
		}


		/*
			APPLY UPDATES
		*/
		foreach ($updateList as $plugin)
		{
            $currentVersion = 0;

            if (isset($this->installedPlugins->{$plugin->id})) {
                $currentVersion = $this->installedPlugins->{$plugin->id}->version;
            }

			Utils::p("Updating " . $plugin->id . " --- " . $currentVersion . " => " . $plugin->version);
			$this->installPlugin($plugin);
		}

		$this->saveInstalledPlugins();
		Utils::p("Done, go check if everything is OK");
		return true;
	}

    /**
     * Installs a plugin in the rust server
     * @param array $plugin
     * @throws Exception
     */
	private function installPlugin ($plugin)
	{
		$pluginId = $plugin->id;
		$download = $this->oxide->downloadPlugin($plugin->id, $plugin->download);

		if (empty($download->content) || empty($download->file)) {
			throw new Exception("Failed to download " . $plugin->id);
		}

		$pathInfo = pathinfo($download->file);

		if ($pathInfo == "zip") {
			// we will have to unzip it first
			$filePath = $this->composerFolder . "tmp/" . $download->file;
			throw new Exception("ZIP plugins are currently not supported");
		} else {
			$filePath = $this->config->oxideFolder . "plugins/" . $download->file;
		}

		$success = file_put_contents($filePath, $download->content);

		if ($success === false) {
			throw new Exception("Couldn't write $filePath");
		}

		// update installed.json
		if (!isset($this->installedPlugins->{$pluginId})) {
			$this->installedPlugins->{$pluginId} = new stdClass();
			$this->installedPlugins->{$pluginId}->time = date("Y-m-d H:i:s");
			$this->installedPlugins->{$pluginId}->version = 0;
		}
		$this->installedPlugins->{$pluginId}->version = $plugin->version;
	}
}
