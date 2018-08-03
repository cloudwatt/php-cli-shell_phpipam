# PHP-CLI SHELL for PHPIPAM

This repository is the addon for PHP-CLI SHELL about PHPIPAM service.  
You have to use base PHP-CLI SHELL project that is here: https://github.com/cloudwatt/php-cli-shell_base


# REQUIREMENTS

#### PHPIPAM
/!\ Tested on PHPIPAM version 1.3.1: https://github.com/phpipam/phpipam
* Copy all custom API controllers located in ressources/ipam on your PHPIPAM instance
    * Cw_addresses.php: /var/www/phpipam/api/controllers/custom/Cw_addresses.php


# INSTALLATION

#### APT PHP
__*https://launchpad.net/~ondrej/+archive/ubuntu/php*__
* add-apt-repository ppa:ondrej/php
* apt-get update
* apt install php7.1-cli php7.1-mbstring php7.1-readline php7.1-curl  
__Do not forget to install php7.1-curl__

#### REPOSITORY
* git clone https://github.com/cloudwatt/php-cli-shell_base
* git checkout tags/v1.0
* git clone https://github.com/cloudwatt/php-cli-shell_phpipam
* git checkout tags/v1.0
* Merge these two repositories

#### CONFIGURATION FILE
* mv config.json.example config.json
* vim config.json
    * servers field contains all PHPIPAM server addresses which must be identified by custom key [IPAM_SERVER_KEY]  
	  __server key must be unique and you will use it on next steps. You have an example in config file__
	* contexts field contains all API application name configured on your PHPIPAM instance  
	  __On your PHPIPAM instance, go to Administration > API and create application without code or security__

#### PHP LAUNCHER FILE
* mv ipam.php.example phpipam.php
* vim phpipam.php
    * Change [IPAM_SERVER_KEY] with the key of your PHPIPAM server in configuration file

#### CREDENTIALS FILE
/!\ For security reason, use a read only account or API app configured in read only mode!  
__*Change informations which are between []*__
* vim credentialsFile
    * read -sr USER_PASSWORD_INPUT
    * export IPAM_[IPAM_SERVER_KEY]_LOGIN=[YourLoginHere]
    * export IPAM_[IPAM_SERVER_KEY]_PASSWORD=$USER_PASSWORD_INPUT  
	__Change [IPAM_SERVER_KEY] with the key of your PHPIPAM server in configuration file__


# EXECUTION

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