<?php
	namespace App\Ipam;

	use Closure;

	use Core as C;
	use Cli as Cli;

	use Addon\Ipam;

	class Shell_Ipam extends Cli\Shell\Browser
	{
		const SHELL_HISTORY_FILENAME = '.ipam.history';

		/**
		  * @var Addon\Ipam\Connector\Abstract
		  */
		protected $_IPAM;

		protected $_commands = array(
			'help', 'history',
			'ls', 'll', 'cd', 'pwd', 'search', 'find', 'exit', 'quit',
			'list' => array(
				'section', 'subnet', 'vlan', 'address',
			),
			'show' => array(
				'section', 'subnet', 'vlan', 'address',
			),
			'create' => array(
				'address'
			),
			'modify' => array(
				'address',
			),
			'remove' => array(
				'address',
			),
			'refresh' => array(
				'caches'
			),
			'phpipam',
		);

		/**
		  * Arguments ne commencant pas par - mais étant dans le flow de la commande
		  *
		  * ls mon/chemin/a/lister
		  * cd mon/chemin/ou/aller
		  * find ou/lancer/ma/recherche
		  */
		protected $_inlineArgCmds = array(
			'ls' => "#^\"?([a-z0-9\-_.: /\\\\\#~]+)\"?$#i",											// / pour path, # pour #IPv4 ou #Ipv6
			'll' => "#^\"?([a-z0-9\-_.: /\\\\\#~]+)\"?$#i",											// / pour path, # pour #IPv4 ou #Ipv6
			'cd' => "#^\"?([a-z0-9\-_. /\\\\\#~]+)\"?$#i",											// / pour path, # pour #IPv4 ou #Ipv6
			'search' => array(
				0 => array('all', 'subnet', 'vlan', 'address'),
				1 => "#^\"?([a-z0-9\-_.:* /\#]+)\"?$#i"
			),
			'find' => array(
				0 => "#^\"?([a-z0-9\-_. /~]+)\"?$#i",
				1 => array('all', 'subnet', 'vlan', 'address'),
				2 => "#^\"?([a-z0-9\-_.:* /\#]+)\"?$#i"												// * pour % SQL LIKE
			),
			'list section' => "#^\"?([a-z0-9\-_. ]+)\"?$#i",
			'show section' => "#^\"?([a-z0-9\-_. ]+)\"?$#i",
			//'list folder' => "#^\"?([a-z0-9\-_. ]+)\"?$#i",
			//'show folder' => "#^\"?([a-z0-9\-_. ]+)\"?$#i",
			'list subnet' => "#^\"?([a-z0-9\-_.: /\#]+)\"?$#i",										// : pour IPv6, / pour CIDR, # pour #IPv4 ou #Ipv6
			'show subnet' => "#^\"?([a-z0-9\-_.: /\#]+)\"?$#i",										// : pour IPv6, / pour CIDR, # pour #IPv4 ou #Ipv6
			'list vlan' => "#^\"?([a-z0-9\-_. ]+)\"?$#i",
			'show vlan' => "#^\"?([a-z0-9\-_. ]+)\"?$#i",
			//'show ip' => "#^\"?(([0-9]{1,3}\.){3}\.[0-9]{1,3)\"?$#i",
			'list address' => "#^\"?(([0-9]{1,3}\.){3}[0-9]{1,3})|([a-z0-9\-_.:* ]+)\"?$#i",		// @todo regexp ipv6
			'show address' => "#^\"?(([0-9]{1,3}\.){3}[0-9]{1,3})|([a-z0-9\-_.:* ]+)\"?$#i",		// @todo regexp ipv6
			'create address' => array(
				"#^\"?(([0-9]{1,3}\.){3}[0-9]{1,3})|([a-z0-9:]+)\"?$#i",							// IP v4 or v6 @todo regexp ipv6
				"#^\"?([a-z0-9\-_.: ]+)\"?$#i",														// hostname
				"#^\"?([a-z0-9\-_.: ]+)\"?$#i",														// description
			),
			'modify address' => array(
				"#^\"?(([0-9]{1,3}\.){3}[0-9]{1,3})|([a-z0-9\-_.:* ]+)\"?$#i",
				array('name', 'hostname', 'description'),
				"#^\"?(.+)\"?$#i"
			),
			'remove address' => array("#^\"?(([0-9]{1,3}\.){3}[0-9]{1,3})|([a-z0-9\-_.:* ]+)\"?$#i"),
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
			'ls' => "Affiche la liste des éléments disponibles",
			'll' => "Alias de ls",
			'cd' => "Permet de naviguer dans l'arborescence",
			'pwd' => "Affiche la position actuelle dans l'arborescence",
			'search' => "Recherche avancée d'éléments. Utilisation: search [type] [recherche]",
			'find' => "Recherche avancée d'éléments. Utilisation: find [localisation|.] [type] [recherche]",
			'exit' => "Ferme le shell",
			'quit' => "Alias de exit",
			'list' => "Affiche un type d'éléments; Dépend de la localisation actuelle. Utilisation: list [section|subnet|vlan|address] [object]",
			'list section' => "Affiche les informations d'une section; Dépend de la localisation",
			//'list folder' => "Affiche les informations d'un dossier; Dépend de la localisation",
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
			'create address' => "Créer une adresse IP. Utilisation: create address [ip] [hostname] [description]",
			'modify address' => "Modifie les informations d'une adresse IP. Utilisation: modify address [hostname|IP] [hostname|description] [value]",
			'remove address' => "Supprime une adresse IP. Utilisation: remove address [hostname|IP]",
			'refresh caches' => "Rafraîchi les caches des objets de l'IPAM",
			'phpipam' => "Lance le site WEB de PHPIPAM",
		);

		/**
		  * @var bool
		  */
		protected $_objectCaching = false;


		public function __construct($configFilename, $server, $autoInitialisation = true)
		{
			parent::__construct($configFilename);

			if(!$this->isOneShotCall()) {
				$printInfoMessages = true;
				ob_end_flush();
			}
			else {
				$printInfoMessages = false;
			}

			try {
				// /!\ Compatible mono-serveur donc $server ne peut pas être un array
				$IPAM = new Ipam\Connector(array($server), $printInfoMessages, null, $this->_addonDebug);
			}
			catch(\Exception $e) {
				$this->error("Impossible de se connecter ou de s'authentifier au service IPAM:".PHP_EOL.$e->getMessage(), 'red');
				exit;
			}

			$this->_IPAM = $IPAM->getIpam();
			Ipam\Api_Abstract::setIpam($this->_IPAM);

			$this->_objectCaching = (bool) $IPAM->getConfig()->objectCaching;
			$this->_initAllApiCaches($this->_objectCaching);

			$this->_PROGRAM = new Shell_Program_Ipam($this, $this->_TERMINAL);

			foreach(array('ls', 'll', 'cd') as $cmd) {
				$this->_inlineArgCmds[$cmd] = Closure::fromCallable(array($this->_PROGRAM, 'shellAutoC_cd'));
				$this->_TERMINAL->setInlineArg($cmd, $this->_inlineArgCmds[$cmd]);
			}

			if($autoInitialisation) {
				$this->_init();
			}
		}

		protected function _initAllApiCaches($state)
		{
			if($state)
			{
				$classes = array(
					'Addon\Ipam\Api_Section',
					'Addon\Ipam\Api_Folder',
					'Addon\Ipam\Api_Subnet',
					'Addon\Ipam\Api_Vlan',
					'Addon\Ipam\Api_Address',
				);

				foreach($classes as $class)
				{
					$this->EOL();
					$class::cache(true);

					$this->print("Initialisation du cache pour les objets ".$class::OBJECT_NAME." ...", 'blue');
					$status = $class::refreshCache($this->_IPAM);
					$this->_TERMINAL->deleteMessage(1, true);

					if($status === true) {
						$this->print("Initialisation du cache pour les objets ".$class::OBJECT_NAME." [OK]", 'green');
					}
					else {
						$this->error("Initialisation du cache pour les objets ".$class::OBJECT_NAME." [KO]", 'red');
						$class::cache(false);
						$this->print("Désactivation du cache pour les objets ".$class::OBJECT_NAME." [OK]", 'orange');
					}
				}
			}
			else {
				$this->error("Le cache des objets est désactivé, pour l'activer éditez la configuration", 'orange');
			}
		}

		protected function _routeShellCmd($cmd, array $args)
		{
			$exit = false;

			switch($cmd)
			{
				case 'search': {
					array_unshift($args, DIRECTORY_SEPARATOR);
					$status = $this->_PROGRAM->printSearchObjects($args);
					break;
				}
				case 'find': {
					$status = $this->_PROGRAM->printSearchObjects($args);
					break;
				}
				case 'list section': {
					$status = $this->_PROGRAM->printSectionInfos($args, true);
					break;
				}
				/*case 'list folder': {
					$status = $this->_PROGRAM->printFolderInfos($args, true);
					break;
				}*/
				case 'list subnet': {
					$status = $this->_PROGRAM->printSubnetInfos($args, true);
					break;
				}
				case 'list vlan': {
					$status = $this->_PROGRAM->printVlanInfos($args, true);
					break;
				}
				//case 'list ip':
				case 'list address': {
					$status = $this->_PROGRAM->printAddressInfos($args, true);
					break;
				}
				case 'show section': {
					$status = $this->_PROGRAM->printSectionInfos($args, false);
					break;
				}
				/*case 'show folder': {
					$status = $this->_PROGRAM->printFolderInfos($args, false);
					break;
				}*/
				case 'show subnet': {
					$status = $this->_PROGRAM->printSubnetInfos($args, false);
					break;
				}
				case 'show vlan': {
					$status = $this->_PROGRAM->printVlanInfos($args, false);
					break;
				}
				//case 'show ip':
				case 'show address': {
					$status = $this->_PROGRAM->printAddressInfos($args, false);
					break;
				}
				case 'create address': {
					$status = $this->_PROGRAM->createAddress($args);
					break;
				}
				case 'modify address': {
					$status = $this->_PROGRAM->modifyAddress($args);
					break;
				}
				case 'remove address': {
					$status = $this->_PROGRAM->removeAddress($args);
					break;
				}
				case 'refresh caches': {
					$this->_initAllApiCaches($this->_objectCaching);
					$status = true;
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

			if(isset($status)) {
				$this->_routeShellStatus($cmd, $status);
			}

			return $exit;
		}

		protected function _moveToRoot()
		{
			if($this->_pathIds === null || $this->_pathApi === null)
			{	
				$Ipam_Api_Section = new Ipam\Api_Section();
				$Ipam_Api_Section->setSectionLabel(DIRECTORY_SEPARATOR);

				$this->_pathIds[] = null;
				$this->_pathApi[] = $Ipam_Api_Section;
			}

			return parent::_moveToRoot();
		}

		public function browser(array &$pathIds, array &$pathApi, $path)
		{
			if(C\Tools::is('string', $path)) {
				$path = explode(DIRECTORY_SEPARATOR, $path);
			}

			/**
			  * Utiliser pour Addon\Ipam\Api_Subnet la fonction
			  * permettant de rechercher à la fois un nom et un subnet
			  */
			$cases = array(
				'Addon\Ipam\Api_Section' => array(
					'Addon\Ipam\Api_Section' => 'findSections',
					'Addon\Ipam\Api_Folder' => 'findFolders',
					'Addon\Ipam\Api_Subnet' => 'findSubnets',
				),
				'Addon\Ipam\Api_Folder' => array(
					'Addon\Ipam\Api_Folder' => 'findFolders',
					'Addon\Ipam\Api_Subnet' => 'findSubnets',
				),
				'Addon\Ipam\Api_Subnet' => array(
					'Addon\Ipam\Api_Subnet' => 'findSubnets',
				),
			);

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

						if(array_key_exists($objectApiClass, $cases))
						{
							foreach($cases[$objectApiClass] as $objectClass => $objectMethod)
							{
								switch($objectClass)
								{
									/**
									  * Voir méthode _shellAutoC_cd_browser de IPAM PROGRAM
									  * Un subnet sans nom sera modifié pour qu'il ait son subnet comme nom
									  * / étant un DIRECTORY_SEPARATOR il est remplacé par _ d'où le preg_replace
									  */
									case 'Addon\Ipam\Api_Subnet':
									{
										$part = $this->_PROGRAM->cleanSubnetNameOfIPv($part, $IPv);
										$part = $this->_PROGRAM->formatSubnetPathToCidr($part);

										$args = array($part);

										if($IPv === 4 || $IPv === 6) {
											$args[] = $IPv;
										}

										break;
									}
									default: {
										$args = array($part);
									}
								}

								$objects = call_user_func_array(array($objectApi, $objectMethod), $args);

								if(is_array($objects))
								{
									if(count($objects) === 1) {
										$objectId = $objects[0][$objectClass::FIELD_ID];
									}
									else
									{
										$objectNames = array_column($objects, $objectClass::FIELD_NAME, $objectClass::FIELD_ID);
										$objectIds = array_keys($objectNames, $part, true);

										switch(count($objectIds))
										{
											case 0: {
												continue(2);
											}
											case 1: {
												$objectId = $objectIds[0];
												break;
											}
											default: {
												continue(2);
												// Si count > 1 alors on continue, est-ce correct?
												//break(2);
											}
										}
									}

									$pathApi[] = $temp = new $objectClass($objectId);
									$pathIds[] = $objectId;
									break;
								}
							}
						}
					}
				}
			}
		}
	}