OxideComposer - Plugin Managment for OxideMod
========================================

Composer helps you declare, manage and install plugins of Oxidemod.org for Linux servers.

**BETA** Only tested in Rust Experimental server.

![Ocomposer](https://raw.githubusercontent.com/tetreum/ocomposer/master/screenshots/screen1.jpg)
![Ocomposer2](https://raw.githubusercontent.com/tetreum/ocomposer/master/screenshots/screen2.jpg)

Installation / Usage
--------------------

1.  Download the [`ocomposer`](http://gameriso.com/installer) executable or use the installer as root.

    ``` sh
    $ curl -s http://gameriso.com/installer | bash
    ```
2. Create a `ocomposer.json` in the same folder as `/RustDedicated_Data/` is located and set the following vars:

    ``` json
    {
        "plugins": {},
        "oxideFolder": "server\/rust-server\/oxide\/",
        "login": {
            "user": "USERNAME",
            "password": "PASSWORD"
        }
    }
    ```

    Login data is used to login in Oxidemod.org to download the plugins and check for updates.

    OxideFolder must be relative.

    If you have already installed some plugins, list them in plugins attrs, ex:

    I'm running a Rust server and i have installed Kits plugin.
    1. Go to the plugin profile page: http://oxidemod.org/plugins/kits.668/
    2. Copy from the path the string after `/plugins/` => `kits.668` and set it on ocomposer.json
    ``` json
    {
        "plugins": {
            "kits.668": false
        },
        "oxideFolder": "server\/rust-server\/oxide\/",
        "login": {
            "user": "USERNAME",
            "password": "PASSWORD"
        }
    }
    ```
    The false means you wont let Ocomposer update this plugin on major releases. Ex:
    ```
    Your plugin version is 1.4.
    Latest version is 2.0.
    Update denied. It will have to be manually done.
    ```
    ```
    Your plugin version is 1.4.
    Latest version is 1.5.
    Update will be made.
    ```

3. Validate your changes by running `ocomposer validate`
4. Run `ocomposer update` to check for updates. A ZIP backup of plugins & it's data will be made before each update. The backups will be located in `composer/backups/` folder.

Install new plugins
------------

1. Run `ocomposer install PLUGIN_ID` . Ex: `ocomposer install stack-size-controller.1185` .

Requirements
------------

- PHP 5.5  or above.
- zip (`apt-get install zip`)


ToDo
------------
- Ask OxideMod admins for a json api
- remove command
- restore command
- Tests

License
-------

OComposer is licensed under the MIT License - see the LICENSE file for details
