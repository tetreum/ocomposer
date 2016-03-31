OxideComposer - Plugin Management for OxideMod
========================================

OComposer helps you manage and install plugins of Oxidemod.org for Linux servers.

**BETA** Only tested in Rust Experimental server.

Installation
--------------------

![Ocomposer](https://raw.githubusercontent.com/tetreum/ocomposer/master/screenshots/ocomposer.gif)

1.  Download the [`ocomposer`](https://raw.githubusercontent.com/tetreum/ocomposer/master/compiled/installer) executable or use the installer as root.

    ``` sh
    $ curl -s https://raw.githubusercontent.com/tetreum/ocomposer/master/compiled/installer | bash
    ```
2. Run `ocomposer` in the same folder as `/RustDedicated_Data/` is located and follow the setup steps.


Install new plugins/list already installed ones
------------

1. Run `ocomposer install PLUGIN_ID` . 

Example: having `http://oxidemod.org/plugins/stack-size-controller.1185/` => `ocomposer install stack-size-controller.1185` .

Check for updates
------------

1. Run `ocomposer update` to check for updates. A ZIP backup of plugins & it's data will be made before each update. The backups will be located in `composer/backups/` folder.

Requirements
------------

- PHP 5.5 or above.
- curl (`apt-get install php5 php5-curl`)
- zip (`apt-get install zip`)


ToDo
------------
- remove command
- restore command
- Tests

License
-------

OComposer is licensed under the MIT License - see the LICENSE file for details
