PHP-CLI SHELL for PHPIPAM
-------------------

__New release will be only available on https://github.com/Renji-FR/PhpCliShell__

This repository is the addon for PHP-CLI SHELL about PHPIPAM service.  
You have to use base PHP-CLI SHELL project that is here: https://github.com/cloudwatt/php-cli-shell_base


REQUIREMENTS
-------------------

#### PHPIPAM
/!\ Tested on PHPIPAM version 1.3.1: https://github.com/phpipam/phpipam
* Copy all custom API controllers located in addons/ipam/ressources on your PHPIPAM instance
    * Cw_sections.php: /var/www/phpipam/api/controllers/custom/Cw_sections.php
	* Cw_subnets.php: /var/www/phpipam/api/controllers/custom/Cw_subnets.php
	* Cw_vlans.php: /var/www/phpipam/api/controllers/custom/Cw_vlans.php
	* Cw_addresses.php: /var/www/phpipam/api/controllers/custom/Cw_addresses.php  

__*/!\ Do not rename custom controllers*__  
__*/!\ Version 2.0 add new profiles!*__


INSTALLATION
-------------------

#### APT PHP
Ubuntu only, you can get last PHP version from this PPA:  
__*https://launchpad.net/~ondrej/+archive/ubuntu/php*__
* add-apt-repository ppa:ondrej/php
* apt update

You have to install a PHP version >= 7.1:
* apt install php7.3-cli php7.3-mbstring php7.3-readline php7.3-soap php7.3-curl  

For MacOS users which use PHP 7.3, there is an issue with PCRE.
You have to add this configuration in your php.ini:
```ini
pcre.jit=0
```
*To locate your php.ini, use this command: php -i | grep "Configuration File"*


## USE PHAR

Download last PHAR release and its key from [releases](https://github.com/cloudwatt/php-cli-shell_phpipam/releases)

![wizard](documentation/readme/wizard.gif)

Print wizard help:  
`$ php php-cli-shell.phar --help`

Create PHPIPAM configuration with command:  
`$ php php-cli-shell.phar configuration:application:factory ipam`  
*For more informations about configuration file, see 'CONFIGURATION FILE' section*

Create PHPIPAM launcher with command:  
`$ php php-cli-shell.phar launcher:application:factory ipam`

__*The PHAR contains all PHP-CLI SHELL components (Base, DCIM, IPAM and Firewall)*__


## USE SOURCE

#### REPOSITORY
* git clone https://github.com/cloudwatt/php-cli-shell_base
* git checkout tags/v2.1.2
* git clone https://github.com/cloudwatt/php-cli-shell_phpipam
* git checkout tags/v2.1.2
* Merge these two repositories

#### CONFIGURATION FILE
* mv configurations/ipam.json.example configurations/ipam.json
* vim configurations/ipam.json
    * servers field contains all PHPIPAM server which must be identified by custom key [IPAM_SERVER_KEY]  
	  __server key must be unique and you will use it on next steps. You have an example in config file__
	* contexts section contains all API application name configured on your PHPIPAM instance  
	  __On your PHPIPAM instance, go to Administration > API and create application without code or security__
* Optionnal
    * You can create user configuration files to overwrite some configurations  
	  These files will be ignored for commits, so your user config files can not be overwrited by a futur release
	* mv configurations/ipam.user.json.example configurations/ipam.user.json
	* vim configurations/ipam.user.json  
	  Change configuration like browserCmd
	* All *.user.json files are ignored by .gitignore

#### PHP LAUNCHER FILE
* mv ipam.php.example phpipam.php
* vim phpipam.php
    * Change [IPAM_SERVER_KEY] with the key of your PHPIPAM server in configuration file


EXECUTION
-------------------

#### CREDENTIALS FILE
/!\ For security reason, you can use a read only account or API app configured in read only mode!  
__*Change informations which are between []*__
* vim credentialsFile
    * read -sr USER_PASSWORD_INPUT
    * export IPAM_[IPAM_SERVER_KEY]_LOGIN=[YourLoginHere]
    * export IPAM_[IPAM_SERVER_KEY]_PASSWORD=$USER_PASSWORD_INPUT  
          __Change [IPAM_SERVER_KEY] with the key of your PHPIPAM server in configuration file__

#### SHELL
Launch PHP-CLI Shell for PHPIPAM service
* source credentialsFile
* php phpipam.php

#### COMMAND
Get command result in order to handle with your OS shell.  
/!\ The result is JSON so you can use JQ https://stedolan.github.io/jq/  
__*Change informations which are between []*__
* source credentialsFile
* php phpipam.php "[myCommandHere]"
