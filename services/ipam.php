<?php
	include_once('abstract.php');
	include_once('shell/ipam.php');
	include_once(__DIR__ . '/../classes/rest.php');
	include_once(__DIR__ . '/../ipam/abstract.php');
	include_once(__DIR__ . '/../ipam/api/abstract.php');
	include_once(__DIR__ . '/../ipam/api/section.php');
	include_once(__DIR__ . '/../ipam/api/subnet/abstract.php');
	include_once(__DIR__ . '/../ipam/api/folder.php');
	include_once(__DIR__ . '/../ipam/api/subnet.php');
	include_once(__DIR__ . '/../ipam/api/vlan.php');
	include_once(__DIR__ . '/../ipam/api/address.php');

	class IPAM extends IPAM_Abstract {}

	class SHELL extends Shell_Abstract {}

	class Service_Ipam extends Service_Abstract
	{
		const SHELL_HISTORY_FILENAME = '.ipam.history';

		protected $_IPAM;

		protected $_commands = array(
			'help', 'history', 'cdautocomplete',
			'ls', 'll', 'cd', 'pwd', 'find', 'exit', 'quit',
			'list' => array(
				'section', 'subnet', 'vlan', 'address',
			),
			'show' => array(
				'section', 'subnet', 'vlan', 'address',
			),
		);

		/**
		  * Arguments ne commencant pas par - mais étant dans le flow de la commande
		  *
		  * ls mon/chemin/a/lister
		  * cd mon/chemin/ou/aller
		  * find ou/lancer/ma/recherche
		  */
		protected $_inlineArgCmds = array(
			'cdautocomplete' => array(0 => array('enable', 'en', 'disable', 'dis')),
			'ls' => "#^\"?([a-z0-9\-_.: /~]+)\"?$#i",
			'll' => "#^\"?([a-z0-9\-_. /~]+)\"?$#i",
			'cd' => "#^\"?([a-z0-9\-_. /~]+)\"?$#i",
			'find' => array(
				0 => "#^\"?([a-z0-9\-_. /~]+)\"?$#i",
				1 => array('all', 'subnet', 'vlan', 'address'),
				2 => "#^\"?([a-z0-9\-_.:* /\#]+)\"?$#i"),											// * pour % SQL LIKE
			'list section' => "#^\"?([a-z0-9\-_. ]+)\"?$#i",
			'show section' => "#^\"?([a-z0-9\-_. ]+)\"?$#i",
			//'show folder' => "#^\"?([a-z0-9\-_. ]+)\"?$#i",
			'list subnet' => "#^\"?([a-z0-9\-_.: /\#]+)\"?$#i",										// : pour IPv6, / pour CIDR, # pour #IPv4 ou #Ipv6
			'show subnet' => "#^\"?([a-z0-9\-_.: /\#]+)\"?$#i",										// : pour IPv6, / pour CIDR, # pour #IPv4 ou #Ipv6
			'list vlan' => "#^\"?([a-z0-9\-_. ]+)\"?$#i",
			'show vlan' => "#^\"?([a-z0-9\-_. ]+)\"?$#i",
			//'show ip' => "#^\"?(([0-9]{1,3}\.){3}\.[0-9]{1,3)\"?$#i",
			'list address' => "#^\"?(([0-9]{1,3}\.){3}[0-9]{1,3})|([a-z0-9\-_.:* ]+)\"?$#i",
			'show address' => "#^\"?(([0-9]{1,3}\.){3}[0-9]{1,3})|([a-z0-9\-_.:* ]+)\"?$#i",
			// @todo regexp ipv6
			'phpipam',
		);

		/**
		  * Arguments commencant pas par - ou -- donc hors flow de la commande
		  *
		  * find ... -type [type] -name [name]
		  */
		protected $_outlineArgCmds = array(
		);

		protected $_manCommands = array(
			'history' => "Affiche l'historique des commandes",
			'cdautocomplete' => "Active (enable|en) ou désactive (disable|dis) l'autocompletion de la commande cd",
			'ls' => "Affiche la liste des éléments disponibles",
			'll' => "Alias de ls",
			'cd' => "Permet de naviguer dans l'arborescence",
			'pwd' => "Affiche la position actuelle dans l'arborescence",
			'find' => "Recherche avancée d'éléments. Utilisation: find [localisation|.] [type] [recherche]",
			'exit' => "Ferme le shell",
			'quit' => "Alias de exit",
			'list' => "Affiche un type d'éléments; Dépend de la localisation actuelle. Utilisation: list [section|subnet|vlan|address] [object]",
			'list section' => "Affiche les informations d'une section; Dépend de la localisation",
			'list subnet' => "Affiche les informations d'un sous réseau; Dépend de la localisation",
			'list vlan' => "Affiche les informations d'un VLAN; Dépend de la localisation",
			'list address' => "Affiche les informations d'une adresse IP; Dépend de la localisation. Utilisation: list address [ip|hostname|description]",
			'show' => "Affiche un type d'éléments; Ne dépend pas de la localisation actuelle. Utilisation: show [section|subnet|vlan|address] [object]",
			'show section' => "Affiche les informations d'une section",
			//'show folder' => "Affiche les informations d'un dossier",
			'show subnet' => "Affiche les informations d'un sous réseau",
			'show vlan' => "Affiche les informations d'un VLAN",
			//'show ip ' => "Alias de show address",
			'show address' => "Affiche les informations d'une adresse IP. Utilisation: show address [ip|hostname|description]",
			'phpipam' => "Lance le site WEB de PHPIPAM",
		);

		protected $_cdautocomplete = false;


		public function __construct($configFilename, $server, $autoInitialisation = true)
		{
			parent::__construct($configFilename);

			$printInfoMessages = !$this->isOneShotCall();

			$IPAM = new IPAM(array($server), $printInfoMessages);
			$this->_IPAM = $IPAM->getIpam();
			Ipam_Api_Abstract::setIpam($this->_IPAM);

			$this->_Service_Shell = new Service_Shell_Ipam($this);

			if($autoInitialisation) {
				$this->_init();
			}
		}

		protected function _launchShell($welcomeMessage = true, $goodbyeMessage = true)
		{
			$exit = false;
			$this->_preLauchingShell($welcomeMessage);

			while(!$exit)
			{
				list($cmd, $args) = $this->_SHELL->launch();

				$this->_preRoutingShellCmd($cmd, $args);
				$exit = $this->_routeShellCmd($cmd, $args);
				$this->_postRoutingShellCmd($cmd, $args);
			}

			$this->_postLauchingShell($goodbyeMessage);
		}

		protected function _routeShellCmd($cmd, array $args)
		{
			$exit = false;

			switch($cmd)
			{
				case 'find': {
					$status = $this->_Service_Shell->printSearchObjects($args);
					break;
				}
				case 'list section': {
					$status = $this->_Service_Shell->printSectionInfos($args, true);
					break;
				}
				//case 'list folder':
				case 'list subnet': {
					$status = $this->_Service_Shell->printSubnetInfos($args, true);
					break;
				}
				case 'list vlan': {
					$status = $this->_Service_Shell->printVlanInfos($args, true);
					break;
				}
				//case 'list ip':
				case 'list address': {
					$status = $this->_Service_Shell->printAddressInfos($args, true);
					break;
				}
				case 'show section': {
					$status = $this->_Service_Shell->printSectionInfos($args, false);
					break;
				}
				//case 'show folder':
				case 'show subnet': {
					$status = $this->_Service_Shell->printSubnetInfos($args, false);
					break;
				}
				case 'show vlan': {
					$status = $this->_Service_Shell->printVlanInfos($args, false);
					break;
				}
				//case 'show ip':
				case 'show address': {
					$status = $this->_Service_Shell->printAddressInfos($args, false);
					break;
				}
				case 'phpipam':
				{
					$webUrl = $this->_IPAM->getWebUrl();
					$cmd = $this->_CONFIG->DEFAULT->sys->browserCmd;

					$this->deleteWaitingMsg();
					$handle = popen($cmd.' "'.$webUrl.'" > /dev/null 2>&1', 'r');
					pclose($handle);
					break;
				}
				default: {
					$exit = parent::_routeShellCmd($cmd, $args);
				}
			}

			if(isset($status) && !$status) {
				$this->deleteWaitingMsg();
				$msg = Tools::e($this->_manCommands[$cmd], 'red', false, false, true);
				$this->_SHELL->printMessage($msg);
			}

			return $exit;
		}

		protected function _setObjectAutocomplete(array $fields = null)
		{
			if($fields === null) {
				$fields = array('section', 'folder', 'subnet');
			}
			return parent::_setObjectAutocomplete($fields);
		}

		protected function _moveToRoot()
		{
			if($this->_pathIds === null || $this->_pathApi === null) {
				$this->_pathIds[] = null;
				$this->_pathApi[] = new Ipam_Api_Section();
			}

			return parent::_moveToRoot();
		}

		public function browser(&$pathIds, &$pathApi, $path)
		{
			if(Tools::is('string', $path)) {
				$path = explode('/', $path);
			}

			foreach($path as $index => $part)
			{
				switch($part)
				{
					case '':
					case '~':
					{
						if($index === 0) {
							array_splice($pathIds, 1);
							array_splice($pathApi, 1);
						}
						break;
					}
					case '.': {
						break;
					}
					case '..':
					{
						if(count($pathApi) > 1) {
							array_pop($pathIds);
							array_pop($pathApi);
						}
						break;
					}
					default:
					{
						$objectApi = end($pathApi);
						$objectApiClass = get_class($objectApi);

						/**
						  * @todo a décommenter après correction bug PHPIPAM
						  * curl -vvv -H "token: [token]" -H "Content-Type: application/json; charset=utf-8" https://ipam.corp.cloudwatt.com/api/myAPP/folders/1185/
						  * Fatal error</b>:  Unsupported operand types in <b>/opt/phpipam/functions/classes/class.Tools.php</b> on line <b>1695</b>
						  */
						$cases = array(
							'Ipam_Api_Section' => array(
								'Ipam_Api_Section' => 'getSubSectionId',
								//'Ipam_Api_Folder' => 'getFolderId',
								'Ipam_Api_Subnet' => 'getSubnetId',
							),
							/*'Ipam_Api_Folder' => array(
								'Ipam_Api_Folder' => 'getSubFolderId',
								'Ipam_Api_Subnet' => 'getSubnetId',
							),*/
							'Ipam_Api_Subnet' => array(
								'Ipam_Api_Subnet' => 'getSubSubnetId',
							),
						);

						if(array_key_exists($objectApiClass, $cases))
						{
							foreach($cases[$objectApiClass] as $objectClass => $objectMethod)
							{
								switch($objectClass)
								{
									case 'Ipam_Api_Folder':
									case 'Ipam_Api_Subnet': {
										$part = preg_replace('/(#IPv[46])$/i', '', $part);
										break;
									}
								}

								$objectId = $objectApi->{$objectMethod}($part);

								if($objectId !== false) {
									$pathIds[] = $objectId;
									$pathApi[] = new $objectClass($objectId);
									break;
								}
							}
						}
					}
				}
			}
		}
	}