<?php
	namespace App\Ipam;

	use Core as C;

	use Cli as Cli;

	use Addon\Ipam;

	class Shell_Program_Ipam extends Cli\Shell\Program\Browser
	{
		const OBJECT_NAMES = array(
				Ipam\Api_Section::OBJECT_TYPE => Ipam\Api_Section::OBJECT_NAME,
				Ipam\Api_Folder::OBJECT_TYPE => Ipam\Api_Folder::OBJECT_NAME,
				Ipam\Api_Subnet::OBJECT_TYPE => Ipam\Api_Subnet::OBJECT_NAME,
				Ipam\Api_Address::OBJECT_TYPE => Ipam\Api_Address::OBJECT_NAME,
		);

		const RESULT_KEYS = array(
				Ipam\Api_Section::OBJECT_TYPE => 'sections',
				Ipam\Api_Folder::OBJECT_TYPE => 'folders',
				Ipam\Api_Subnet::OBJECT_TYPE => 'subnets',
				Ipam\Api_Address::OBJECT_TYPE => 'addresses',
		);

		protected $_OPTION_FIELDS = array(
			'section' => array(
				'fields' => array('name'),
			),
			'folder' => array(
				'fields' => array('name'),
			),
			'subnet' => array(
				'fields' => array('name'),
			)
		);

		protected $_LIST_TITLES = array(
			'section' => 'SECTIONS',
			'folder' => 'FOLDERS',
			'subnet' => 'SUBNETS',
			'vlan' => 'VLANS',
			'address' => 'ADDRESSES',
		);

		protected $_LIST_FIELDS = array(
			'section' => array(
				'fields' => false,
				'format' => false
			),
			'folder' => array(
				'fields' => false,
				'format' => false
			),
			'subnet' => array(
				'fields' => false,
				'format' => false
			),
			'vlan' => array(
				'fields' => false,
				'format' => false,
				'subnet' => array(
					'fields' => array('description', 'subnet', 'mask'),
					'format' => '- %s (%s/%d)'
				)
			),
			'address' => array(
				'fields' => false,
				'format' => false
			)
		);

		protected $_PRINT_TITLES = array(
			'section' => 'SECTIONS',
			'folder' => 'FOLDERS',
			'subnet' => 'SUBNETS',
			'vlan' => 'VLANS',
			'address' => 'ADDRESSES',
		);

		protected $_PRINT_FIELDS = array(
			'section' => array(
				'header' => '%s',
				'name' => PHP_EOL.'Name: %s',
				'description' => 'Description: %s',
			),
			'folder' => array(
				'header' => '%s',
				'name' => PHP_EOL.'Name: %s',
				'network' => 'Network: %s',
				'cidrMask' => 'CIDR mask: %d',
				'netMask' => 'NET mask: %s',
				'sectionName' => 'Section: %s',
			),
			'subnet' => array(
				'header' => '%s',
				'name' => PHP_EOL.'Name: %s',
				'network' => 'Network: %s',
				'cidrMask' => 'CIDR mask: %d',
				'netMask' => 'NET mask: %s',
				'firstIP' => 'First IP: %s',
				'lastIP' => 'Last IP: %s',
				'gateway' => 'Gateway: %s',
				'vlanNumber' => 'VLAN ID: %d',
				'vlanName' => 'VLAN Name: %s',
				'sectionName' => 'Section: %s',
				'path' => 'Location: %s',
				'usage' => PHP_EOL.'Usage: %s',
			),
			'vlan' => array(
				'header' => '%s',
				'name' => PHP_EOL.'Name: %s',
				'number' => 'Name: %d',
				'description' => 'Description: %s',
				'subnets' => PHP_EOL.'Subnets:'.PHP_EOL.'%s',
			),
			'address' => array(
				'header' => '%s',
				'ip' => PHP_EOL.'IP: %s',
				'cidrMask' => 'CIDR mask: %d',
				'netMask' => 'NET mask: %s',
				'subnet' => 'Subnet: %s',
				'gateway' => 'Gateway: %s',
				'vlanNumber' => 'VLAN ID: %d',
				'VlanName' => 'VLAN Name: %s',
				'hostname' => PHP_EOL.'Hostname: %s',
				'description' => 'Description: %s',
				'status' => 'Statut: %s',
				'note' => 'Note: %s',
				'subnetPath' => PHP_EOL.'Location: %s',
			),
		);

		/**
		  * @var Addon\Ipam\Service_Store
		  */
		protected $_addonStore;

		/**
		  * @var bool
		  */
		protected $_searchfromCurrentPath = true;


		public function __construct(Cli\Shell\Main $SHELL)
		{
			parent::__construct($SHELL);

			$this->_addonStore = Ipam\Orchestrator::getInstance()->service->store;
		}

		// SHOW
		// --------------------------------------------------
		protected function _getView(array $args)
		{
			$args = array_reverse($args);

			if(isset($args[1]) && $args[1] === '|') {
				return $args[0];
			}
			else {
				return false;
			}
		}

		public function listSections(array $args)
		{
			return $this->_listObjects(Ipam\Api_Section::OBJECT_TYPE, $args);
		}

		public function showSections(array $args)
		{
			return $this->_showObjects(Ipam\Api_Section::OBJECT_TYPE, $args);
		}

		public function listFolders(array $args)
		{
			return $this->_listObjects(Ipam\Api_Folder::OBJECT_TYPE, $args);
		}

		public function showFolders(array $args)
		{
			return $this->_showObjects(Ipam\Api_Folder::OBJECT_TYPE, $args);
		}

		public function listSubnets(array $args)
		{
			return $this->_listObjects(Ipam\Api_Subnet::OBJECT_TYPE, $args);
		}

		public function showSubnets(array $args)
		{
			return $this->_showObjects(Ipam\Api_Subnet::OBJECT_TYPE, $args);
		}

		protected function _printSubnetExtra(array $subnets, array $args = null)
		{
			if(count($subnets) === 1)
			{
				$this->_SHELL->displayWaitingMsg(true, false, 'searching IPAM addresses');

				$path = $subnets[0]['path'].'/'.$subnets[0]['name'];
				$objects = $this->_getObjects($path, $args);
				$this->_printObjectsList($objects);

				$this->_RESULTS['addresses'] = $objects['address'];
			}
		}

		public function listVlans(array $args)
		{
			return $this->_listObjects(Ipam\Api_Vlan::OBJECT_TYPE, $args);
		}

		public function showVlans(array $args)
		{
			return $this->_showObjects(Ipam\Api_Vlan::OBJECT_TYPE, $args);
		}

		public function listAddresses(array $args)
		{
			return $this->_listObjects(Ipam\Api_Address::OBJECT_TYPE, $args);
		}

		public function showAddresses(array $args)
		{
			return $this->_showObjects(Ipam\Api_Address::OBJECT_TYPE, $args);
		}

		protected function _listObjects($type, array $args)
		{
			$view = $this->_getView($args);

			switch($view)
			{
				case 'form': {
					$status = $this->_printObjectForm($type, $args, true);
					break;
				}
				default: {
					$status = $this->_printObjectList($type, $args, true);
				}
			}

			return ($status !== false);
		}

		protected function _showObjects($type, array $args)
		{
			$view = $this->_getView($args);

			switch($view)
			{
				case 'form': {
					$status = $this->_printObjectForm($type, $args, false);
					break;
				}
				default: {
					$status = $this->_printObjectList($type, $args, false);
				}
			}

			return ($status !== false);
		}

		protected function _printObjectForm($type, array $args, $fromCurrentPath = true)
		{
			if(isset($args[0]))
			{
				list($items, $resultKey, $objectName) = $this->_getTypeParams($type, $args[0], $fromCurrentPath);
				$status = $this->_printInformations($type, $items);

				if($status === false) {
					$this->_SHELL->error("Objet '".ucfirst($objectName)."' introuvable", 'orange');
				}
				else {
					$this->_printObjectExtra($type, $items, $args);
				}

				$this->_RESULTS[$resultKey] = $items;
				return $items;
			}

			return false;
		}

		protected function _printObjectList($type, array $args, $fromCurrentPath = true)
		{
			if(isset($args[0]))
			{
				list($items, $resultKey, $objectName) = $this->_getTypeParams($type, $args[0], $fromCurrentPath);

				if(count($items) > 0)
				{
					if(!$this->_SHELL->isOneShotCall())
					{
						switch($type)
						{
							case Ipam\Api_Subnet::OBJECT_TYPE:
							{
								foreach($items as &$item)
								{	
									$item = array(
										$item['network'].'/'.$item['cidrMask'],
										$item['name'],
										$item['firstIP'],
										$item['lastIP'],
										$item['vlanNumber'],
										$item['vlanName'],
										$item['sectionName'],
										$item['path'],
									);
								}
								unset($item);
								break;
							}
							case Ipam\Api_Vlan::OBJECT_TYPE:
							{
								foreach($items as &$item)
								{	
									$item = array(
										$item['number'],
										$item['name'],
										$item['description'],
									);
								}
								unset($item);
								break;
							}
							case Ipam\Api_Address::OBJECT_TYPE:
							{
								foreach($items as &$item)
								{	
									$item = array(
										$item['ip'],
										$item['cidrMask'],
										$item['netMask'],
										$item['hostname'],
										$item['description'],
										$item['status'],
										$item['subnet'],
										$item['subnetPath'],
									);
								}
								unset($item);
								break;
							}
							default:
							{
								foreach($items as &$item) {
									unset($item['header']);
								}
								unset($item);
							}
						}
					}

					$this->_printObjectsList(array($type => $items));
					$this->_printObjectExtra($type, $items, $args);
				}
				else {
					$this->_SHELL->error("Aucun objet '".$objectName."' n'a été trouvé", 'orange');
				}

				$this->_RESULTS[$resultKey] = $items;
				return $items;
			}

			return false;
		}

		protected function _getTypeParams($type, $name, $fromCurrentPath)
		{
			switch($type)
			{
				case Ipam\Api_Section::OBJECT_TYPE: {
					$resultKey = 'sections';
					$objectName = Ipam\Api_Section::OBJECT_NAME;
					$items = $this->_getSectionInfos($name, $fromCurrentPath, null);
					break;
				}
				case Ipam\Api_Folder::OBJECT_TYPE: {
					$resultKey = 'folders';
					$objectName = Ipam\Api_Folder::OBJECT_NAME;
					$items = $this->_getFolderInfos($name, $fromCurrentPath, null);
					break;
				}
				case Ipam\Api_Subnet::OBJECT_TYPE:
				{
					$resultKey = 'subnets';
					$objectName = Ipam\Api_Subnet::OBJECT_NAME;
					$items = $this->_getSubnetInfos($name, $fromCurrentPath, null);
					break;
				}
				case Ipam\Api_Vlan::OBJECT_TYPE:
				{
					$resultKey = 'vlans';
					$objectName = Ipam\Api_Vlan::OBJECT_NAME;
					$items = $this->_getVlanInfos($name, $fromCurrentPath, null);
					break;
				}
				case Ipam\Api_Address::OBJECT_TYPE:
				{
					$resultKey = 'addresses';
					$objectName = Ipam\Api_Address::OBJECT_NAME;
					$items = $this->_getAddressInfos($name, $fromCurrentPath, null);
					break;
				}
				default: {
					throw new Exception("Unknown type '".$type."'", E_USER_ERROR);
				}
			}

			return array($items, $resultKey, $objectName);
		}

		protected function _printObjectExtra($type, array $items, array $args)
		{
			switch($type)
			{
				case Ipam\Api_Section::OBJECT_TYPE: {
					break;
				}
				case Ipam\Api_Folder::OBJECT_TYPE: {
					break;
				}
				case Ipam\Api_Subnet::OBJECT_TYPE: {
					$this->_printSubnetExtra($items, $args);
					break;
				}
				case Ipam\Api_Vlan::OBJECT_TYPE: {
					break;
				}
				case Ipam\Api_Address::OBJECT_TYPE: {
					break;
				}
				default: {
					throw new Exception("Unknown type '".$type."'", E_USER_ERROR);
				}
			}
		}
		// --------------------------------------------------

		// OBJECT > SEARCH
		// --------------------------------------------------
		public function printSearchObjects(array $args)
		{
			if(count($args) === 3)
			{
				$time1 = microtime(true);
				$objects = $this->_searchObjects($args[0], $args[1], $args[2]);
				$time2 = microtime(true);

				if($objects !== false)
				{
					$this->_RESULTS->append($objects);
					$this->_SHELL->print('RECHERCHE ('.round($time2-$time1).'s)', 'black', 'white', 'bold');

					if(!$this->_SHELL->isOneShotCall())
					{
						if(isset($objects['subnets']))
						{
							$counter = count($objects['subnets']);
							$this->_SHELL->EOL()->print('SUBNETS ('.$counter.')', 'black', 'white');

							if($counter > 0)
							{
								foreach($objects['subnets'] as &$subnet)
								{	
									$subnet = array(
										$subnet['network'].'/'.$subnet['cidrMask'],
										$subnet['name'],
										$subnet['sectionName'],
									);
								}
								unset($subnet);

								$table = C\Tools::formatShellTable($objects['subnets']);
								$this->_SHELL->print($table, 'grey');
							}
							else {
								$this->_SHELL->error('Aucun résultat', 'orange');
							}
						}

						// @todo gerer l2domains
						if(isset($objects['vlans']))
						{
							$counter = count($objects['vlans']);
							$this->_SHELL->EOL()->print('VLANS ('.$counter.')', 'black', 'white');

							if($counter > 0)
							{
								foreach($objects['vlans'] as &$vlan)
								{
									$vlan = array(
										$vlan['number'],
										$vlan['name'],
										$vlan['description'],
									);
								}
								unset($vlan);

								$table = C\Tools::formatShellTable($objects['vlans']);
								$this->_SHELL->print($table, 'grey');
							}
							else {
								$this->_SHELL->error('Aucun résultat', 'orange');
							}
						}

						if(isset($objects['addresses']))
						{
							$counter = count($objects['addresses']);
							$this->_SHELL->EOL()->print('ADDRESSES ('.$counter.')', 'black', 'white');

							if($counter > 0)
							{
								foreach($objects['addresses'] as &$address)
								{
									$address = array(
										$address['ip'],
										$address['hostname'],
										$address['description'],
										$address['subnetPath'],
									);
								}
								unset($address);

								$table = C\Tools::formatShellTable($objects['addresses']);
								$this->_SHELL->print($table, 'grey');
							}
							else {
								$this->_SHELL->error('Aucun résultat', 'orange');
							}
						}

						$this->_SHELL->EOL();
					}
				}
				else {
					$this->_SHELL->error("Aucun résultat trouvé", 'orange');
				}

				return true;
			}

			return false;
		}

		protected function _searchObjects($path, $objectType, $objectSearch)
		{
			switch($objectType)
			{
				case 'subnet':
				case 'subnets': {
					$subnets = $this->_getSubnetInfos($objectSearch, $this->_searchfromCurrentPath, $path);
					return array('subnets' => $subnets);
				}
				case 'vlan':
				case 'vlans': {
					$vlans = $this->_getVlanInfos($objectSearch, $this->_searchfromCurrentPath, $path);
					return array('vlans' => $vlans);
				}
				case 'address':
				case 'addresses': {
					$addresses = $this->_getAddressInfos($objectSearch, $this->_searchfromCurrentPath, $path);
					return array('addresses' => $addresses);
				}
				case 'all': {
					$subnets = $this->_searchObjects($path, 'subnet', $objectSearch);
					$vlans = $this->_searchObjects($path, 'vlan', $objectSearch);
					$addresses = $this->_searchObjects($path, 'address', $objectSearch);
					return array_merge($subnets, $vlans, $addresses);
				}
				default: {
					throw new Exception("Search item '".$objectType."' is unknow", E_USER_ERROR);
				}
			}
		}

		protected function _getSectionResults($section, $fromCurrentPath = true, $path = null)
		{
			$sections = array();

			if($fromCurrentPath)
			{
				$currentApi = $this->_browser($path);

				if($currentApi instanceof Ipam\Api_Section)
				{
					$sectionId = $currentApi->getSubSectionId($section);

					if(isset($sectionId) && $sectionId !== false) {
						$sections[] = array('id' => $sectionId);
					}
				}
			}
			else
			{
				$sectionNames = Ipam\Api_Section::searchSectionNames($section);

				if(C\Tools::is('array&&count>0', $sectionNames)) {
					$sections = $sectionNames;
				}
			}

			return $sections;
		}

		protected function _getSectionObjects($section, $fromCurrentPath = true, $path = null)
		{
			$sections = $this->_getSectionResults($section, $fromCurrentPath, $path);

			foreach($sections as &$section) {
				$section = Ipam\Api_Section::factory($section['id']);
			}
			unset($section);

			return $sections;
		}

		protected function _getSectionInfos($section, $fromCurrentPath = true, $path = null)
		{
			$items = array();

			$sections = $this->_getSectionObjects($section, $fromCurrentPath, $path);

			foreach($sections as $Ipam_Api_Section)
			{
				$sectionName = $Ipam_Api_Section->getName();

				$item = array();
				$item['header'] = $sectionName;
				$item['name'] = $sectionName;
				$item['description'] = $Ipam_Api_Section->getDescription();

				$items[] = $item;
			}

			return $items;
		}
		
		protected function _getFolderResults($folder, $fromCurrentPath = true, $path = null)
		{
			// @todo a coder
			return array();
		}
		
		protected function _getFolderObjects($folder, $fromCurrentPath = true, $path = null)
		{
			$folders = $this->_getFolderResults($folder, $fromCurrentPath, $path);

			foreach($folders as &$folder) {
				$folder = Ipam\Api_Folder::factory($folder['id']);
			}
			unset($folder);

			return $folders;
		}

		protected function _getFolderInfos($folder, $fromCurrentPath = true, $path = null)
		{
			// @todo a coder
			return array();
		}

		protected function _getSubnetResults($subnet, $fromCurrentPath = true, $path = null)
		{
			$subnets = array();

			if($fromCurrentPath)
			{
				$pathApi = $this->_browser($path, false);

				$currentSectionApi = $this->_getLastSectionPath($pathApi);

				if($currentSectionApi !== false)
				{
					$currentSectionId = $currentSectionApi->getSectionId();
					$currentSubnetApi = $this->_getLastSubnetPath($pathApi);

					if($currentSubnetApi !== false) {
						$currentSubnetId = $currentSubnetApi->getSubnetId();
					}

					$cidrSubnets = Ipam\Api_Subnet::searchCidrSubnets($subnet);
					$subnetNames = Ipam\Api_Subnet::searchSubnetNames($subnet);

					foreach(array($cidrSubnets, $subnetNames) as $_subnets)
					{
						if(C\Tools::is('array&&count>0', $_subnets))
						{
							foreach($_subnets as $subnet)
							{
								if($subnet['sectionId'] === (string) $currentSectionId)
								{
									if(isset($currentSubnetId))
									{
										$Ipam_Api_Subnet = Ipam\Api_Subnet::factory($subnet['id']);

										do
										{
											if($Ipam_Api_Subnet->getSubnetId() === $currentSubnetId) {
												break;
											}
										}
										while(($Ipam_Api_Subnet = $Ipam_Api_Subnet->subnetApi) !== false && $Ipam_Api_Subnet instanceof Ipam\Api_Subnet);
										// /!\ Parent of IPAM subnet can be folder !

										if($Ipam_Api_Subnet !== false) {
											$subnets[] = $subnet;
										}
									}
									else {
										$subnets[] = $subnet;
									}
								}
							}
						}
					}
				}
			}

			if(!isset($cidrSubnets))
			{
				
				$cidrSubnets = Ipam\Api_Subnet::searchCidrSubnets($subnet);
				$subnetNames = Ipam\Api_Subnet::searchSubnetNames($subnet);

				foreach(array($cidrSubnets, $subnetNames) as $_subnets)
				{
					if(C\Tools::is('array&&count>0', $_subnets)) {
						$subnets = array_merge($subnets, $_subnets);
					}
				}
			}

			return $subnets;
		}

		protected function _getSubnetObjects($subnet, $fromCurrentPath = true, $path = null)
		{
			$subnets = $this->_getSubnetResults($subnet, $fromCurrentPath, $path);

			foreach($subnets as &$subnet) {
				$subnet = Ipam\Api_Subnet::factory($subnet['id']);
			}
			unset($subnet);

			return $subnets;
		}

		protected function _getSubnetInfos($subnet, $fromCurrentPath = true, $path = null)
		{
			$items = array();

			$subnet = $this->cleanSubnetNameOfIPv($subnet, $IPv);
			$subnets = $this->_getSubnetObjects($subnet, $fromCurrentPath, $path);

			foreach($subnets as $Ipam_Api_Subnet)
			{
				if(($IPv === 4 || $IPv === 6) && !$Ipam_Api_Subnet->isIPv($IPv)) {
					continue;
				}

				$Ipam_Api_Vlan = $Ipam_Api_Subnet->vlanApi;

				$network = $Ipam_Api_Subnet->getNetwork();
				$cidrMask = $Ipam_Api_Subnet->getCidrMask();

				$item = array();
				$item['header'] = $network.'/'.$cidrMask;
				$item['name'] = $Ipam_Api_Subnet->getSubnetLabel();
				$item['network'] = $network;
				$item['cidrMask'] = $cidrMask;
				$item['netMask'] = $Ipam_Api_Subnet->getNetMask();
				$item['gateway'] = $Ipam_Api_Subnet->getGateway();

				$item['firstIP'] = $Ipam_Api_Subnet->getFirstIp();
				$item['lastIP'] = $Ipam_Api_Subnet->getLastIp();

				// Un subnet n'a pas forcément de VLAN
				if($Ipam_Api_Vlan !== false) {
					$item['vlanNumber'] = $Ipam_Api_Vlan->getNumber();
					$item['vlanName'] = $Ipam_Api_Vlan->getName();
				}
				else {
					$item['vlanNumber'] = '/';
					$item['vlanName'] = '/';
				}

				// Un subnet a forcément une SECTION
				$item['sectionName'] = $Ipam_Api_Subnet->sectionApi->getName();
				$item['path'] = DIRECTORY_SEPARATOR.$Ipam_Api_Subnet->getPath(true, DIRECTORY_SEPARATOR);

				$item['usage'] = '';
				$subnetUsage = $Ipam_Api_Subnet->getUsage();

				foreach($subnetUsage as $fieldName => $fieldValue)
				{
					switch($fieldName)
					{
						case Ipam\Api_Subnet::USAGE_FIELDS['used']:
						case Ipam\Api_Subnet::USAGE_FIELDS['total']:
						case Ipam\Api_Subnet::USAGE_FIELDS['free']: {
							$item['usage'] .= ucwords($fieldName).': '.$fieldValue.' | ';
							break;
						}
					}
				}

				$items[] = $item;
			}

			return $items;
		}

		protected function _getVlanResults($vlan, $fromCurrentPath = true, $path = null)
		{
			$vlans = array();

			if($fromCurrentPath)
			{
				$pathApi = $this->_browser($path, false);

				$currentSectionApi = $this->_getLastSectionPath($pathApi);

				if($currentSectionApi !== false)
				{
					$currentSectionId = $currentSectionApi->getSectionId();
					$currentSubnetApi = $this->_getLastSubnetPath($pathApi);

					if($currentSubnetApi !== false) {
						$currentSubnetId = $currentSubnetApi->getSubnetId();
					}

					$vlanNumbers = Ipam\Api_Vlan::searchVlanNumbers($vlan);
					$vlanNames = Ipam\Api_Vlan::searchVlanNames($vlan);

					foreach(array($vlanNumbers, $vlanNames) as $_vlans)
					{
						if(C\Tools::is('array&&count>0', $_vlans))
						{
							foreach($_vlans as $vlan)
							{
								$Ipam_Api_Vlan = Ipam\Api_Vlan::factory($vlan['id']);
								$subnets = $Ipam_Api_Vlan->getSubnets();

								foreach($subnets as $subnet)
								{
									if((isset($currentSubnetId) && $subnet['id'] === (string) $currentSubnetId) ||
										(!isset($currentSubnetId) && $subnet['sectionId'] === (string) $currentSectionId))
									{
										$vlans[] = $vlan;
										break;
									}
								}
							}
						}
					}
				}
			}

			if(!isset($vlanNumbers))
			{
				$vlanNumbers = Ipam\Api_Vlan::searchVlanNumbers($vlan);
				$vlanNames = Ipam\Api_Vlan::searchVlanNames($vlan);

				foreach(array($vlanNumbers, $vlanNames) as $_vlans)
				{
					if(C\Tools::is('array&&count>0', $_vlans)) {
						$vlans = array_merge($vlans, $_vlans);
					}
				}
			}

			return $vlans;
		}

		protected function _getVlanObjects($vlan, $fromCurrentPath = true, $path = null)
		{
			$vlans = $this->_getVlanResults($vlan, $fromCurrentPath, $path);

			foreach($vlans as &$vlan) {
				$vlan = Ipam\Api_Vlan::factory($vlan['id']);
			}
			unset($vlan);

			return $vlans;
		}

		protected function _getVlanInfos($vlan, $fromCurrentPath = true, $path = null)
		{
			$items = array();

			$vlans = $this->_getVlanObjects($vlan, $fromCurrentPath, $path);

			foreach($vlans as $Ipam_Api_Vlan)
			{
				$vlanName = $Ipam_Api_Vlan->getName();
				$vlanNumber = $Ipam_Api_Vlan->getNumber();
				$subnets = $Ipam_Api_Vlan->getSubnets();

				if($subnets !== false)
				{
					$subnets = C\Tools::arrayFilter($subnets, $this->_LIST_FIELDS['vlan']['subnet']['fields']);

					foreach($subnets as &$subnet) {
						$subnet = vsprintf($this->_LIST_FIELDS['vlan']['subnet']['format'], $subnet);
					}
					unset($subnet);
				}
				else {
					$subnets = array();
				}

				$item = array();
				$item['header'] = $vlanNumber.' '.$vlanName;
				$item['name'] = $vlanName;
				$item['number'] = $vlanNumber;
				$item['description'] = $Ipam_Api_Vlan->getDescription();
				$item['subnets'] = implode(PHP_EOL, $subnets);

				$items[] = $item;
			}

			return $items;
		}

		protected function _getAddressResults($address, $fromCurrentPath = true, $path = null)
		{
			$addresses = array();

			if($fromCurrentPath)
			{
				$pathApi = $this->_browser($path, false);

				$currentSectionApi = $this->_getLastSectionPath($pathApi);

				if($currentSectionApi !== false)
				{
					$currentSectionId = $currentSectionApi->getSectionId();
					$currentSubnetApi = $this->_getLastSubnetPath($pathApi);

					if($currentSubnetApi !== false) {
						$currentSubnetId = $currentSubnetApi->getSubnetId();
					}

					$addressIps = Ipam\Api_Address::searchIpAddresses($address);
					$addressNames = Ipam\Api_Address::searchAddressNames($address);
					$addressDescs = Ipam\Api_Address::searchAddressDescs($address);

					foreach(array($addressIps, $addressNames, $addressDescs) as $_addresses)
					{
						if(C\Tools::is('array&&count>0', $_addresses))
						{
							foreach($_addresses as $address)
							{
								$Ipam_Api_Subnet = Ipam\Api_Subnet::factory($address['subnetId']);

								if($Ipam_Api_Subnet->getSectionId() === $currentSectionId)
								{
									if(isset($currentSubnetId))
									{
										do
										{
											if($Ipam_Api_Subnet->getSubnetId() === $currentSubnetId) {
												break;
											}
										}
										while(($Ipam_Api_Subnet = $Ipam_Api_Subnet->subnetApi) !== false);

										if($Ipam_Api_Subnet !== false) {
											$addresses[] = $address;
										}
									}
									else {
										$addresses[] = $address;
									}
								}
							}
						}
					}
				}
			}

			if(!isset($addressIps))
			{
				$addressIps = Ipam\Api_Address::searchIpAddresses($address);
				$addressNames = Ipam\Api_Address::searchAddressNames($address);
				$addressDescs = Ipam\Api_Address::searchAddressDescs($address);

				foreach(array($addressIps, $addressNames, $addressDescs) as $_addresses)
				{
					if(C\Tools::is('array&&count>0', $_addresses)) {
						$addresses = array_merge($addresses, $_addresses);
					}
				}
			}

			return $addresses;
		}

		protected function _getAddressObjects($address, $fromCurrentPath = true, $path = null)
		{
			$addresses = $this->_getAddressResults($address, $fromCurrentPath, $path);

			foreach($addresses as &$address) {
				$address = Ipam\Api_Address::factory($address['id']);
			}
			unset($address);

			return $addresses;
		}

		protected function _getAddressInfos($address, $fromCurrentPath = true, $path = null)
		{
			$items = array();

			$addresses = $this->_getAddressObjects($address, $fromCurrentPath, $path);

			foreach($addresses as $Ipam_Api_Address)
			{
				$Ipam_Api_Subnet = $Ipam_Api_Address->getSubnetApi();
				$Ipam_Api_Vlan = $Ipam_Api_Subnet->getVlanApi();

				$ip = $Ipam_Api_Address->getIp();
				$cidrMask = $Ipam_Api_Subnet->getCidrMask();

				$item = array();
				$item['header'] = $ip.'/'.$cidrMask;
				$item['ip'] = $ip;
				$item['cidrMask'] = $cidrMask;
				$item['netMask'] = $Ipam_Api_Subnet->getNetMask();
				$item['subnet'] = $Ipam_Api_Subnet->getCidrSubnet();
				$item['gateway'] = $Ipam_Api_Subnet->getGateway();
				$item['hostname'] = $Ipam_Api_Address->getHostname();
				$item['description'] = $Ipam_Api_Address->getDescription();
				$item['status'] = ucfirst(Ipam\Api_Address::TAGS[$Ipam_Api_Address->getTag()]);
				$item['note'] = $Ipam_Api_Address->getNote();
				$item['subnetPath'] = DIRECTORY_SEPARATOR.$Ipam_Api_Subnet->getPath(true, DIRECTORY_SEPARATOR);

				if($Ipam_Api_Vlan instanceof Ipam\Api_Vlan) {
					$item['vlanNumber'] = $Ipam_Api_Vlan->getNumber();
					$item['VlanName'] = $Ipam_Api_Vlan->getName();
				}

				$items[] = $item;
			}

			return $items;
		}
		// --------------------------------------------------

		// // Service_Cli_Abstract : SYSTEM METHODS
		// --------------------------------------------------
		public function printObjectInfos(array $args, $fromCurrentContext = true)
		{
			// /!\ ls AUB --> On ne doit pas afficher AUB mais le contenu de AUB !
			/*$objectApi = end($this->_pathApi);

			switch(get_class($objectApi))
			{
				case 'Addon\Ipam\Api_Section':
					$cases = array(
						'section' => '_getSectionInfos',
						'folder' => '_getFolderInfos',
						'subnet' => '_getSubnetInfos'
					);
					break;
				case 'Addon\Ipam\Api_Folder':
					$cases = array(
						'folder' => '_getFolderInfos',
						'subnet' => '_getSubnetInfos'
					);
					break;
				case 'Addon\Ipam\Api_Subnet':
					$cases = array(
						'subnet' => '_getSubnetInfos',
						'address' => '_getAddressInfos'
					);
					break;
				default:
					$cases = array();
			}*/

			$cases = array(
				'address' => '_getAddressInfos'
			);

			$result = $this->_printObjectInfos($cases, $args, $fromCurrentContext);

			if($result !== false) {
				list($status, $objectType, $infos) = $result;

				/**
				  * /!\ Attention aux doublons lorsque printObjectsList est appelé manuellement
				  * Voir code pour ls ou ll dans services/browser méthode _routeShellCmd
				  */
				/*if($status && $objectType === 'subnet') {
					$this->printSubnetExtra($infos);
				}*/

				return $status;
			}
			else {
				return false;
			}
		}

		protected function _getObjects($context = null, array $args = null)
		{
			$path = $context;

			$items = array(
				Ipam\Api_Section::OBJECT_TYPE => array(),
				Ipam\Api_Folder::OBJECT_TYPE => array(),
				Ipam\Api_Subnet::OBJECT_TYPE => array(),
				Ipam\Api_Vlan::OBJECT_TYPE => array(),
				Ipam\Api_Address::OBJECT_TYPE => array(),
			);

			$currentApi = $this->_browser($path);
			$currentType = $currentApi::OBJECT_TYPE;

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

			if(array_key_exists($currentType, $cases))
			{
				foreach($cases[$currentType] as $objectClass => $objectMethod)
				{
					if($objectMethod !== false) {
						$objects = call_user_func(array($currentApi, $objectMethod), '*');
					}
					else {
						$objects = false;
					}

					if(C\Tools::is('array&&count>0', $objects))
					{
						$objectType = $objectClass::OBJECT_TYPE;

						foreach($objects as $object)
						{
							switch($objectType)
							{
								case Ipam\Api_Subnet::OBJECT_TYPE:
								{
									if(!C\Tools::is('string&&!empty', $object[$objectClass::FIELD_NAME])) {
										$object[$objectClass::FIELD_NAME] = $object['subnet'].'/'.$object['mask'];
									}
									else {
										$object = $this->formatSubnetNameWithIPv($object, true);
									}

									$items[$objectType][] = array(
										'name' => $object[$objectClass::FIELD_NAME],
										'subnet' => $object['subnet'],
										'mask' => $object['mask'],
									);
									break;
								}
								default: {
									$items[$objectType][] = array('name' => $object[$objectClass::FIELD_NAME]);
								}
							}
						}
					}
					elseif($currentApi instanceof Ipam\Api_Subnet)
					{
						$vlanId = $currentApi->getVlanId();

						if($vlanId !== false)
						{
							$Ipam_Api_Vlan = Ipam\Api_Vlan::factory($vlanId);

							$vlanNumber = $Ipam_Api_Vlan->getNumber();
							$vlanLabel = $Ipam_Api_Vlan->getName();

							$items[Ipam\Api_Vlan::OBJECT_TYPE][] = array(
								'number' => $vlanNumber,
								'name' => $vlanLabel,
							);
						}

						$addresses = $currentApi->getAddresses();

						if($addresses !== false)
						{
							foreach($addresses as $address)
							{
								$items[Ipam\Api_Address::OBJECT_TYPE][] = array(
									'ip' => $address['ip'],
									'hostname' => $address['hostname'],
									'description' => $address['description'],
									'status' => ucfirst(Ipam\Api_Address::TAGS[$address['tag']]),
								);
							}
						}
					}
				}
			}

			/**
			  * /!\ index 0 doit toujours être le nom de l'objet ou l'identifiant (VlanID, IP)
			  */
			$compare = function($a, $b) {
				return strnatcasecmp(current($a), current($b));
			};

			usort($items[Ipam\Api_Section::OBJECT_TYPE], $compare);
			usort($items[Ipam\Api_Folder::OBJECT_TYPE], $compare);
			usort($items[Ipam\Api_Subnet::OBJECT_TYPE], $compare);
			usort($items[Ipam\Api_Vlan::OBJECT_TYPE], $compare);
			usort($items[Ipam\Api_Address::OBJECT_TYPE], $compare);

			return array(
				'section' => $items[Ipam\Api_Section::OBJECT_TYPE],
				'folder' => $items[Ipam\Api_Folder::OBJECT_TYPE],
				'subnet' => $items[Ipam\Api_Subnet::OBJECT_TYPE],
				'vlan' => $items[Ipam\Api_Vlan::OBJECT_TYPE],
				'address' => $items[Ipam\Api_Address::OBJECT_TYPE]
			);
		}
		// --------------------------------------------------

		// ADDRESSES : CREATE & MODIFY & REMOVE
		// --------------------------------------------------
		public function createAddress(array $args)
		{
			if(count($args) >= 2)
			{
				$Ipam_Api_Subnet = $this->_getLastSubnetPath($this->_pathApi);

				if($Ipam_Api_Subnet !== false)
				{
					$Ipam_Api_Address = new Ipam\Api_Address();
					$status = $Ipam_Api_Address->setSubnetApi($Ipam_Api_Subnet);

					if($status)
					{
						$status = $Ipam_Api_Address->setAddress($args[0]);

						if($status)
						{
							$status = $Ipam_Api_Address->setAddressLabel($args[1]);

							if($status)
							{
								$description = (isset($args[2])) ? ($args[2]) : ('');

								try {
									$status = $Ipam_Api_Address->create($description);
								}
								catch(\Exception $exception) {
									$this->_SHELL->error("L'adresse IP '".$args[0]."' n'a pas pu être créée: ".$exception->getMessage(), 'orange');
									$status = false;
								}

								if($status) {
									$this->_SHELL->print("L'adresse IP '".$args[0]."' a bien été créée dans le subnet '".$Ipam_Api_Subnet->name."'", 'green');
								}
								else
								{
									if($Ipam_Api_Address->hasErrorMessage()) {
										$this->_SHELL->error($Ipam_Api_Address->getErrorMessage(), 'orange');
									}
									else {
										$this->_SHELL->error("Impossible de créer l'adresse IP '".$args[0]."' dans le subnet '".$Ipam_Api_Subnet->name."'", 'orange');
									}
								}
							}
							else {
								$this->_SHELL->error("Le hostname '".$args[1]."' n'est pas valide", 'orange');
							}
						}
						else {
							$this->_SHELL->error("L'adresse IP '".$args[0]."' n'est pas valide ou n'appartient pas au subnet '".$Ipam_Api_Subnet->name."'", 'orange');
						}
					}
					else {
						$this->_SHELL->error("Unable to find the subnet to create address", 'orange');
					}
				}
				else {
					$this->_SHELL->error("Merci de vous déplacer dans un subnet avant de créer une adresse IP", 'orange');
				}

				return true;
			}

			return false;
		}

		public function modifyAddress(array $args)
		{
			if(count($args) >= 3)
			{
				$Ipam_Api_Subnet = $this->_getLastSubnetPath($this->_pathApi);
				$subnetId = ($Ipam_Api_Subnet !== false) ? ($Ipam_Api_Subnet->id) : (null);

				$addresses = Ipam\Api_Address::searchAddresses($args[0], null, $subnetId, true);

				if($addresses !== false)
				{
					switch(count($addresses))
					{
						case 0: {
							$this->_SHELL->error("Aucune adresse n'a été trouvée durant la recherche de '".$args[0]."'", 'orange');
							break;
						}
						case 1: {
							$addressId = $addresses[0][Ipam\Api_Address::FIELD_ID];
							$Ipam_Api_Address = Ipam\Api_Address::factory($addressId);
							break;
						}
						default: {
							$this->_SHELL->error("Plusieurs adresses ont été trouvées durant la recherche de '".$args[0]."'", 'orange');
						}
					}
				}
				else {
					$this->_SHELL->error("Une erreur s'est produite durant la recherche de l'adresse '".$args[0]."'", 'orange');
				}

				if(isset($Ipam_Api_Address))
				{
					if($Ipam_Api_Subnet === false) {
						$Ipam_Api_Subnet = $Ipam_Api_Address->subnetApi;
					}

					switch($args[1])
					{
						case 'name':
						case 'hostname': {
							$status = $Ipam_Api_Address->renameHostname($args[2]);
							break;
						}
						case 'description': {
							$status = $Ipam_Api_Address->setDescription($args[2]);
							break;
						}
						default: {
							$this->_SHELL->error("L'attribut '".$args[1]."' n'est pas valide pour une adresse IP", 'orange');
							return false;
						}
					}

					if($status) {
						$this->_SHELL->print("L'adresse IP '".$Ipam_Api_Address->label."' du subnet '".$Ipam_Api_Subnet->name."' a été modifiée!", 'green');
					}
					else
					{
						if($Ipam_Api_Address->hasErrorMessage()) {
							$this->_SHELL->error($Ipam_Api_Address->getErrorMessage(), 'orange');
						}
						else {
							$this->_SHELL->error("L'adresse IP '".$Ipam_Api_Subnet->address."' du subnet '".$Ipam_Api_Subnet->name."' n'a pas pu être modifiée!", 'orange');
						}
					}
				}

				return true;
			}

			return false;
		}

		public function removeAddress(array $args)
		{
			if(isset($args[0]))
			{
				$Ipam_Api_Subnet = $this->_getLastSubnetPath($this->_pathApi);
				$subnetId = ($Ipam_Api_Subnet !== false) ? ($Ipam_Api_Subnet->id) : (null);

				$addresses = Ipam\Api_Address::searchAddresses($args[0], null, $subnetId, true);

				if($addresses !== false)
				{
					switch(count($addresses))
					{
						case 0: {
							$this->_SHELL->error("Aucune adresse n'a été trouvée durant la recherche de '".$args[0]."'", 'orange');
							break;
						}
						case 1: {
							$addressId = $addresses[0][Ipam\Api_Address::FIELD_ID];
							$Ipam_Api_Address = Ipam\Api_Address::factory($addressId);
							break;
						}
						default: {
							$this->_SHELL->error("Plusieurs adresses ont été trouvées durant la recherche de '".$args[0]."'", 'orange');
						}
					}
				}
				else {
					$this->_SHELL->error("Une erreur s'est produite durant la recherche de l'adresse '".$args[0]."'", 'orange');
				}

				if(isset($Ipam_Api_Address))
				{
					if($Ipam_Api_Subnet === false) {
						$Ipam_Api_Subnet = $Ipam_Api_Address->subnetApi;
					}

					$Cli_Terminal_Question = new Cli\Terminal\Question();

					$question = "Etes-vous certain de vouloir supprimer cette adresse '".$Ipam_Api_Address->ip."' '".$Ipam_Api_Address->name."' du subnet '".$Ipam_Api_Subnet->name."' ? [Y|n]";
					$question = C\Tools::e($question, 'red', false, false, true);
					$answer = $Cli_Terminal_Question->question($question);
					$answer = mb_strtolower($answer);

					if($answer === 'y' || $answer === 'yes')
					{
						$status = $Ipam_Api_Address->remove();

						if($status) {
							$this->_SHELL->print("L'adresse IP '".$args[0]."' du subnet '".$Ipam_Api_Subnet->name."' a bien été supprimée", 'green');
						}
						else
						{
							if($Ipam_Api_Address->hasErrorMessage()) {
								$this->_SHELL->error($Ipam_Api_Address->getErrorMessage(), 'orange');
							}
							else {
								$this->_SHELL->error("Impossible de supprimer l'adresse IP '".$args[0]."' du subnet '".$Ipam_Api_Subnet->name."'", 'orange');
							}
						}
					}
				}

				return true;
			}

			return false;
		}
		// --------------------------------------------------

		protected function _getLastSectionPath(array $pathApi)
		{
			$lastSectionApi = $this->_searchLastPathApi($pathApi, 'Addon\Ipam\Api_Section');
			// /!\ La toute 1ere section n'existe pas, voir App\Ipam\Shell_Ipam::_moveToRoot
			return ($lastSectionApi !== false && $lastSectionApi->sectionExists()) ? ($lastSectionApi) : (false);
		}

		protected function _getLastSubnetPath(array $pathApi)
		{
			return $this->_searchLastPathApi($pathApi, 'Addon\Ipam\Api_Subnet');
		}

		public function subnetNameHasIPv($name, &$IPv = array())
		{
			return (bool) preg_match('/(#IPv[46])$/i', $name, $IPv);
		}

		public function cleanSubnetNameOfIPv($name, &$IPv = null)
		{
			$hasIPv = $this->subnetNameHasIPv($name, $IPv);

			if($hasIPv) {
				$IPv = (int) substr($IPv[0], -1, 1);
				$name = preg_replace('/(#IPv[46])$/i', '', $name);
			}
			else {
				$IPv = false;
			}

			return $name;
		}

		public function formatSubnetNameWithIPv(array $subnet, $throwException = true)
		{
			$cidrSubnet = $subnet['subnet'].'/'.$subnet['mask'];

			if(Ipam\Tools::isSubnetV4($cidrSubnet)) {
				$subnet[Ipam\Api_Subnet::FIELD_NAME] .= '#IPv4';
			}
			elseif(Ipam\Tools::isSubnetV6($cidrSubnet)) {
				$subnet[Ipam\Api_Subnet::FIELD_NAME] .= '#IPv6';
			}
			elseif($throwException) {
				throw new Exception("Subnet '".$subnet[Ipam\Api_Subnet::FIELD_NAME]."' is not a valid IPv4/IPv6 subnet", E_USER_ERROR);
			}
			else {
				return false;
			}

			return $subnet;
		}

		public function formatSubnetCidrToPath($subnet)
		{
			if(is_array($subnet)) {
				$subnet[Ipam\Api_Subnet::FIELD_NAME] = str_replace('/', '_', $subnet[Ipam\Api_Subnet::FIELD_NAME]);
			}
			elseif(is_string($subnet)) {
				$subnet = str_replace('/', '_', $subnet);
			}
			else {
				return false;
			}

			return $subnet;
		}

		public function formatSubnetPathToCidr($subnet)
		{
			return preg_replace('#^([0-9.:]+)_([0-9]{1,3})$#i', '\1/\2', $subnet);
		}

		// ----------------- AutoCompletion -----------------
		public function shellAutoC_cd($cmd, $search = null)
		{
			$Core_StatusValue = new C\StatusValue(false, array());

			if($search === null) {
				$search = '';
			}
			elseif($search === false) {
				return $Core_StatusValue;
			}

			/**
			  * /!\ Pour eviter le double PHP_EOL (celui du MSG et de la touche ENTREE)
			  * penser à désactiver le message manuellement avec un lineUP
			  */
			$this->_SHELL->displayWaitingMsg(true, false, 'Searching IPAM objects');

			if($search !== '' && $search !== DIRECTORY_SEPARATOR && substr($search, -1, 1) !== DIRECTORY_SEPARATOR) {
				$search .= DIRECTORY_SEPARATOR;
			}

			$input = $search;
			$firstChar = substr($search, 0, 1);

			if($firstChar === DIRECTORY_SEPARATOR) {
				$mode = 'absolute';
				$input = substr($input, 1);						// Pour le explode / implode
				$search = substr($search, 1);					// Pour le explode / foreach
				$baseApi = $this->_getRootPathApi();
				$pathApi = array($baseApi);
			}
			elseif($firstChar === '~') {
				return $Core_StatusValue;
			}
			else {
				$mode = 'relative';
				$pathApi = $this->_getPathApi();
				$baseApi = $this->_getCurrentPathApi();
			}

			/*$this->_SHELL->print('MODE: '.$mode.PHP_EOL, 'green');
			$this->_SHELL->print('PATH: '.$baseApi->getPath(true, DIRECTORY_SEPARATOR).PHP_EOL, 'orange');
			$this->_SHELL->print('INPUT: '.$input.PHP_EOL, 'green');
			$this->_SHELL->print('SEARCH: '.$search.PHP_EOL, 'green');*/

			$searchParts = explode(DIRECTORY_SEPARATOR, $search);

			foreach($searchParts as $index => $search)
			{
				$baseApi = end($pathApi);

				if($search === '..')
				{
					if(count($pathApi) > 1) {
						$status = false;
						$results = array();
						array_pop($pathApi);
					}
					else {
						continue;
					}
				}
				else
				{
					$Core_StatusValue__browser = $this->_shellAutoC_cd_browser($baseApi, $search);

					$status = $Core_StatusValue__browser->status;
					$result = $Core_StatusValue__browser->result;

					if(is_array($result))
					{
						if($status === false && count($result) === 0)
						{
							// empty directory
							if($search === '') {
								$status = true;
								$results = array('');	// Workaround retourne un seul resultat avec en clé input et en valeur ''
							}
							// no result found
							else
							{
								$Core_StatusValue__browser = $this->_shellAutoC_cd_browser($baseApi, null);

								if($Core_StatusValue__browser instanceof C\StatusValue) {
									$status = $Core_StatusValue__browser->status;
									$results = $Core_StatusValue__browser->results;
								}
								// /!\ Ne doit jamais se réaliser!
								else {
									return $Core_StatusValue;
								}
							}

							break;
						}
						else {
							$status = false;
							$results = $result;
							break;
						}
					}
					elseif($result instanceof Ipam\Api_Abstract) {
						$pathApi[] = $result;
						$results = array('');			// Workaround retourne un seul resultat avec en clé input et en valeur ''
					}
					// /!\ Ne doit jamais se réaliser!
					else {
						return $Core_StatusValue;
					}
				}
			}

			$parts = explode(DIRECTORY_SEPARATOR, $input);
			array_splice($parts, $index, count($parts), '');
			$input = implode(DIRECTORY_SEPARATOR, $parts);

			/*$this->_SHELL->print('index: '.$index.PHP_EOL, 'red');
			$this->_SHELL->print('count: '.count($parts).PHP_EOL, 'red');*/

			if($mode === 'absolute') {
				$input = DIRECTORY_SEPARATOR.$input;
			}

			//$this->_SHELL->print('INPUT: '.$input.PHP_EOL, 'blue');

			$options = array();

			foreach($results as $result)
			{
				if($result !== '') {
					$result .= DIRECTORY_SEPARATOR;
				}

				$options[$input.$result] = $result;
			}

			/*$this->_SHELL->print('STATUS: '.$status.PHP_EOL, 'blue');
			$this->_SHELL->print('OPTIONS: '.PHP_EOL, 'blue');
			var_dump($options); $this->_SHELL->EOL();*/
			
			$Core_StatusValue->setStatus($status);
			$Core_StatusValue->setOptions($options);

			// Utile car la désactivation doit s'effectuer avec un lineUP, voir message plus haut
			$this->_SHELL->deleteWaitingMsg(true);

			return $Core_StatusValue;
		}

		/**
		  * @param Addon\Ipam\Api_Abstract $baseApi
		  * @param null|string $search
		  * @return Core\StatusValue
		  */
		protected function _shellAutoC_cd_browser($baseApi, $search = null)
		{
			$sections = true;
			$folders = true;
			$subnets = true;

			$status = false;
			$results = array();
			$baseApiClassName = get_class($baseApi);

			if($baseApiClassName === 'Addon\Ipam\Api_Section')
			{
				$sections = $baseApi->findSections($search.'*', false);

				if($sections !== false)
				{
					$sections = array_column($sections, Ipam\Api_Section::FIELD_NAME, Ipam\Api_Section::FIELD_ID);

					if(($sectionId = array_search($search, $sections, true)) !== false) {
						$results = Ipam\Api_Section::factory($sectionId);
					}
					elseif(count($sections) > 0) {
						$results = array_merge($results, array_values($sections));
					}
				}
			}

			if($baseApiClassName === 'Addon\Ipam\Api_Section' || $baseApiClassName === 'Addon\Ipam\Api_Folder')
			{
				$folders = $baseApi->findFolders($search.'*', false);

				if($folders !== false)
				{
					$folders = array_column($folders, Ipam\Api_Folder::FIELD_NAME, Ipam\Api_Folder::FIELD_ID);

					if(($folderId = array_search($search, $folders, true)) !== false) {
						$results = Ipam\Api_Folder::factory($folderId);
					}
					elseif(count($folders) > 0) {
						$results = array_merge($results, array_values($folders));
					}
				}
			}

			if($baseApiClassName === 'Addon\Ipam\Api_Section' || $baseApiClassName === 'Addon\Ipam\Api_Folder' || $baseApiClassName === 'Addon\Ipam\Api_Subnet')
			{
				$userSearch = $search;

				/**
				  * Clean search without IP version
				  */
				$search = $this->cleanSubnetNameOfIPv($search, $IPv);

				/**
				  * Un subnet sans nom sera modifié pour qu'il ait son subnet comme nom
				  * / étant un DIRECTORY_SEPARATOR il est remplacé par _ d'où le preg_replace
				  */
				$subnet = $this->formatSubnetPathToCidr($search);

				if(Ipam\Tools::isSubnet($subnet)) {
					$search = $subnet;
					$wc = '';
				}
				else {
					$wc = '*';
				}

				$subnets = $baseApi->findSubnets($search.$wc, $IPv, false);

				if($subnets !== false)
				{
					/**
					  * /!\ $search ne contient pas forcément un nom de subnet
					  * mais aussi un subnet partiel ou un subnet complet
					  *
					  * Dans le cas où il n'y ait pas de résultats alors
					  * on recherche l'ensemble des possibilités (*)
					  * puis on ne garde que ce qui correspond
					  */
					if(count($subnets) === 0) {
						$wcSearch = true;
						$subnets = $baseApi->findSubnets('*', $IPv, false);
					}

					if($subnets !== false)
					{
						array_walk($subnets, function (&$subnet)
						{
							if(!C\Tools::is('string&&!empty', $subnet[Ipam\Api_Subnet::FIELD_NAME])) {
								$subnet[Ipam\Api_Subnet::FIELD_NAME] = $subnet['subnet'].'/'.$subnet['mask'];
							}
							else {
								$subnet = $this->formatSubnetNameWithIPv($subnet);
							}

							$subnet = $this->formatSubnetCidrToPath($subnet);
						});
						unset($subnet);

						$subnetNames = array_column($subnets, Ipam\Api_Subnet::FIELD_NAME, Ipam\Api_Subnet::FIELD_ID);

						if(isset($wcSearch))
						{
							$subnetNames = preg_grep('#^('.preg_quote($userSearch, '#').')#i', $subnetNames);
							$subnetIds = array_keys($subnetNames);

							$subnets = array_filter($subnets, function($subnet) use ($subnetIds) {
								return in_array($subnet[Ipam\Api_Subnet::FIELD_ID], $subnetIds, false);
								// /!\ $subnet[Ipam\Api_Subnet::FIELD_ID] is a string
							});
						}

						if(($subnetId = array_search($userSearch, $subnetNames, true)) !== false) {
							// $status = true;		// Un subnet peut toujours contenir un autre subnet
							$results = Ipam\Api_Subnet::factory($subnetId);
						}
						elseif(count($subnetNames) > 0) {
							$results = array_merge($results, array_values($subnetNames));
						}
					}
				}
			}

			if(is_array($results))
			{
				/**
				  * Si aucun des recherches ne fonctionnent ou si plusieurs résultats ont été trouvés mais qu'aucun ne correspond à la recherche
				  * alors cela signifie qu'on est arrivé au bout du traitement, on ne pourrait pas aller plus loin, donc on doit retourner true
				  */
				$status = (($sections === false && $folders === false && $subnets === false) || count($results) > 0);
			}

			return new C\StatusValue($status, $results);
		}
		// --------------------------------------------------
	}