<?php
	namespace App\Ipam;

	use Closure;

	use Core as C;
	use Cli as Cli;

	use Addon\Ipam;

	class Shell_Ipam extends Cli\Shell\Browser
	{
		const SHELL_HISTORY_FILENAME = '.ipam.history';

		const REGEX_SECTION_NAME = "#^\"?([a-z0-9\-_. ]+)\"?$#i";
		const REGEX_SECTION_NAME_WC = "#^\"?([a-z0-9\-_. *]+)\"?$#i";
		const REGEX_SUBNET_ALL = "#^\"?([a-z0-9\-_.: /\#]+)\"?$#i";
		const REGEX_SUBNET_ALL_WC = "#^\"?([a-z0-9\-_.: /\#*]+)\"?$#i";
		const REGEX_VLAN_ALL = "#^\"?([a-z0-9\-_. ]+)\"?$#i";
		const REGEX_VLAN_ALL_WC = "#^\"?([a-z0-9\-_. *]+)\"?$#i";
		const REGEX_ADDRESS_ALL = "#^\"?(([0-9]{1,3}\.){3}[0-9]{1,3})|([a-z0-9\-_.: ]+)\"?$#i";
		const REGEX_ADDRESS_ALL_WC = "#^\"?(([0-9]{1,3}\.){3}[0-9]{1,3})|([a-z0-9\-_.: *]+)\"?$#i";

		protected $_commands = array(
			'help', 'history',
			'ls', 'll', 'cd', 'pwd', 'search', 'find', 'exit', 'quit',
			'list' => array(
				'sections', 'subnets', 'vlans', 'addresses',
			),
			'show' => array(
				'sections', 'subnets', 'vlans', 'addresses',
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
			'ls' => "#^\"?([a-z0-9\-_.: /\\\\\#~]+)\"?$#i",																		// / pour path, # pour #IPv4 ou #Ipv6
			'll' => "#^\"?([a-z0-9\-_.: /\\\\\#~]+)\"?$#i",																		// / pour path, # pour #IPv4 ou #Ipv6
			'cd' => "#^\"?([a-z0-9\-_. /\\\\\#~]+)\"?$#i",																		// / pour path, # pour #IPv4 ou #Ipv6
			'search' => array(
				0 => array('all', 'subnets', 'vlans', 'addresses'),
				1 => "#^\"?([a-z0-9\-_.:* /\#]+)\"?$#i"
			),
			'find' => array(
				0 => "#^\"?([a-z0-9\-_. /~]+)\"?$#i",
				1 => array('all', 'subnets', 'vlans', 'addresses'),
				2 => "#^\"?([a-z0-9\-_.:* /\#]+)\"?$#i"																			// * pour % SQL LIKE
			),
			'list sections' => array(0 => self::REGEX_SECTION_NAME, 1 => array('|'), 2 => array('form', 'list')),
			'list subnets' => array(0 => self::REGEX_SUBNET_ALL, 1 => array('|'), 2 => array('form', 'list')),					// : pour IPv6, / pour CIDR, # pour #IPv4 ou #Ipv6
			'list vlans' => array(0 => self::REGEX_VLAN_ALL, 1 => array('|'), 2 => array('form', 'list')),
			'list addresses' => array(0 => self::REGEX_ADDRESS_ALL_WC, 1 => array('|'), 2 => array('form', 'list')),			// @todo regexp ipv6
			'show sections' => array(0 => self::REGEX_SECTION_NAME, 1 => array('|'), 2 => array('form', 'list')),
			'show subnets' => array(0 => self::REGEX_SUBNET_ALL, 1 => array('|'), 2 => array('form', 'list')),					// : pour IPv6, / pour CIDR, # pour #IPv4 ou #Ipv6
			'show vlans' => array(0 => self::REGEX_VLAN_ALL, 1 => array('|'), 2 => array('form', 'list')),
			'show addresses' => array(0 => self::REGEX_ADDRESS_ALL_WC, 1 => array('|'), 2 => array('form', 'list')),			// @todo regexp ipv6
			'create address' => array(
				"#^\"?(([0-9]{1,3}\.){3}[0-9]{1,3})|([a-f0-9:]+)\"?$#i",														// IP v4 or v6 @todo regexp ipv6
				"#^\"?([[:print:]]+)\"?$#i",																					// hostname
				"#^\"?([[:print:]]+)\"?$#i",																					// description
			),
			'modify address' => array(
				"#^\"?(([0-9]{1,3}\.){3}[0-9]{1,3})|([a-f0-9:]+)|([[:print:]]+)\"?$#i",											// IP v4 or v6 @todo regexp ipv6 or hostname
				array('name', 'hostname', 'description'),
				"#^\"?([:print:]+)\"?$#i"
			),
			'remove address' => array("#^\"?(([0-9]{1,3}\.){3}[0-9]{1,3})|([a-f0-9:]+)|([[:print:]]+)\"?$#i"),					// IP v4 or v6 @todo regexp ipv6 or hostname
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
			'list' => "Affiche un type d'éléments; Dépend de la localisation actuelle. Utilisation: list [sections|subnets|vlans|addresses] [object] | [form|list]",
			'list sections' => "Affiche les informations d'une ou plusieurs sections; Dépend de la localisation. Utilisation: show sections [name] | [form|list]",
			'list subnets' => "Affiche les informations d'un ou plusieurs sous réseau; Dépend de la localisation. Utilisation: show subnets [name|subnet] | [form|list]",
			'list vlans' => "Affiche les informations d'un ou plusieurs VLAN; Dépend de la localisation. Utilisation: show vlans [name|number] | [form|list]",
			'list addresses' => "Affiche les informations d'une ou plusieurs adresses IP; Dépend de la localisation. Utilisation: list addresses [ip|hostname|description] | [form|list]",
			'show' => "Affiche un type d'éléments; Ne dépend pas de la localisation actuelle. Utilisation: show [sections|subnets|vlans|addresses] [object] | [form|list]",
			'show sections' => "Affiche les informations d'une ou plusieurs sections. Utilisation: show sections [name] | [form|list]",
			'show subnets' => "Affiche les informations d'un ou plusieurs sous réseau. Utilisation: show subnets [name|subnet] | [form|list]",
			'show vlans' => "Affiche les informations d'un ou plusieurs VLAN. Utilisation: show vlans [name|number] | [form|list]",
			'show addresses' => "Affiche les informations d'une ou plusieurs adresses IP. Utilisation: show addresses [ip|hostname|description] | [form|list]",
			'create' => "Créer un objet IPAM",
			'create address' => "Créer une adresse IP. Utilisation: create address [ip] [hostname] [description]",
			'modify' => "Modifier un objet IPAM",
			'modify address' => "Modifie les informations d'une adresse IP. Utilisation: modify address [hostname|IP] [hostname|description] [value]",
			'remove' => "Supprimer un objet IPAM",
			'remove address' => "Supprime une adresse IP. Utilisation: remove address [hostname|IP]",
			'refresh caches' => "Rafraîchi les caches des objets de l'IPAM",
			'phpipam' => "Lance le site WEB de PHPIPAM",
		);

		/**
		  * @var Addon\Ipam\Service
		  */
		protected $_addonService;


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

			$this->_initAddon($server, $printInfoMessages);

			$this->_PROGRAM = new Shell_Program_Ipam($this, $this->_TERMINAL);

			foreach(array('ls', 'll', 'cd') as $cmd) {
				$this->_inlineArgCmds[$cmd] = Closure::fromCallable(array($this->_PROGRAM, 'shellAutoC_cd'));
				$this->_TERMINAL->setInlineArg($cmd, $this->_inlineArgCmds[$cmd]);
			}

			if($autoInitialisation) {
				$this->_init();
			}
		}

		protected function _initAddon($server, $printInfoMessages)
		{
			$Ipam_Orchestrator = Ipam\Orchestrator::getInstance($this->_CONFIG->IPAM);
			$this->_addonService = $Ipam_Orchestrator->debug($this->_addonDebug)->newService($server);

			if($printInfoMessages) {
				$adapterMethod = $this->_addonService->getMethod();
				C\Tools::e(PHP_EOL."Connection ".$adapterMethod." à l'IPAM @ ".$server." veuillez patienter ... ", 'blue');
			}

			try {
				$isReady = $this->_addonService->initialization();
			}
			catch(\Exception $e) {
				if($printInfoMessages) { C\Tools::e("[KO]", 'red'); }
				$this->error("Impossible de démarrer le service IPAM:".PHP_EOL.$e->getMessage(), 'red');
				exit;
			}

			if(!$isReady) {
				if($printInfoMessages) { C\Tools::e("[KO]", 'red'); }
				$this->error("Le service IPAM n'a pas pu être correctement initialisé", 'red');
				exit;
			}

			if($printInfoMessages) {
				C\Tools::e("[OK]", 'green');
			}

			$this->_refreshAddonCaches();
		}

		protected function _refreshAddonCaches()
		{
			$state = (bool) $this->_addonService->config->objectCaching;

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
					$cache = $status = $this->_addonService->cache;

					/**
					  * Do not forget to enable cache
					  * Test if cache is enabled
					  */
					if($cache !== false && $cache->enable())
					{
						$this->print("Initialisation du cache pour les objets ".$class::OBJECT_NAME." ...", 'blue');
						$status = $cache->refresh($class::OBJECT_TYPE);
						$this->_TERMINAL->deleteMessage(1, true);

						if($status === true) {
							$this->print("Initialisation du cache pour les objets ".$class::OBJECT_NAME." [OK]", 'green');
						}
						else {
							$this->error("Initialisation du cache pour les objets ".$class::OBJECT_NAME." [KO]", 'red');
							$this->print("Désactivation du cache pour les objets ".$class::OBJECT_NAME." [OK]", 'orange');
							$cache->erase($class::OBJECT_TYPE);
						}
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
				case 'list sections': {
					$status = $this->_PROGRAM->listSections($args);
					break;
				}
				case 'list subnets': {
					$status = $this->_PROGRAM->listSubnets($args);
					break;
				}
				case 'list vlans': {
					$status = $this->_PROGRAM->listVlans($args);
					break;
				}
				case 'list addresses': {
					$status = $this->_PROGRAM->listAddresses($args);
					break;
				}
				case 'show sections': {
					$status = $this->_PROGRAM->showSections($args);
					break;
				}
				case 'show subnets': {
					$status = $this->_PROGRAM->showSubnets($args);
					break;
				}
				case 'show vlans': {
					$status = $this->_PROGRAM->showVlans($args);
					break;
				}
				case 'show addresses': {
					$status = $this->_PROGRAM->showAddresses($args);
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
					$this->_refreshAddonCaches();
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
				Ipam\Api_Section::OBJECT_TYPE => array(
					Ipam\Api_Section::class => 'findSections',
					Ipam\Api_Folder::class => 'findFolders',
					Ipam\Api_Subnet::class => 'findSubnets',
				),
				Ipam\Api_Folder::OBJECT_TYPE => array(
					Ipam\Api_Folder::class => 'findFolders',
					Ipam\Api_Subnet::class => 'findSubnets',
				),
				Ipam\Api_Subnet::OBJECT_TYPE => array(
					Ipam\Api_Subnet::class => 'findSubnets',
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
						$objectType = $objectApi::OBJECT_TYPE;

						if(array_key_exists($objectType, $cases))
						{
							foreach($cases[$objectType] as $objectClass => $objectMethod)
							{
								switch($objectClass)
								{
									/**
									  * Voir méthode _shellAutoC_cd_browser de IPAM PROGRAM
									  * Un subnet sans nom sera modifié pour qu'il ait son subnet comme nom
									  * / étant un DIRECTORY_SEPARATOR il est remplacé par _ d'où le preg_replace
									  */
									case Ipam\Api_Subnet::class:
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

									$pathApi[] = $objectClass::factory($objectId);
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