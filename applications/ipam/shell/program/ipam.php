<?php
	namespace App\Ipam;

	use Core as C;

	use Cli as Cli;

	use Addon\Ipam;

	class Shell_Program_Ipam extends Cli\Shell\Program\Browser
	{
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
				'fields' => array('name'),
				'format' => '%s'
			),
			'folder' => array(
				'fields' => array('name'),
				'format' => '%s'
			),
			'subnet' => array(
				'fields' => array('name', 'subnet', 'mask'),
				'format' => '%s - %s/%d'
			),
			'vlan' => array(
				'fields' => array('number', 'name'),
				'format' => '%d - %s',
				'subnet' => array(
					'fields' => array('description', 'subnet', 'mask'),
					'format' => '- %s (%s/%d)'
				)
			),
			'address' => array(
				'fields' => array('ip', 'hostname', 'description', 'status'),
				'format' => '(%4$s) %1$s [%2$s] {%3$s}'
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
				'networkIP' => 'Network IP: %s',
				'broadcastIP' => 'Broadcast IP: %s',
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

		protected $_searchfromCurrentPath = true;


		protected function _getObjects($context = null)
		{
			$path = $context;

			$items = array(
				Ipam\Api_Section::OBJECT_KEY => array(),
				Ipam\Api_Folder::OBJECT_KEY => array(),
				Ipam\Api_Subnet::OBJECT_KEY => array(),
				Ipam\Api_Vlan::OBJECT_KEY => array(),
				Ipam\Api_Address::OBJECT_KEY => array(),
			);

			$currentApi = $this->_browser($path);
			$currentApiClass = get_class($currentApi);

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

			if(array_key_exists($currentApiClass, $cases))
			{
				foreach($cases[$currentApiClass] as $objectClass => $objectMethod)
				{
					if($objectMethod !== false) {
						$objects = call_user_func(array($currentApi, $objectMethod), '*');
					}
					else {
						$objects = false;
					}

					if(C\Tools::is('array&&count>0', $objects))
					{
						foreach($objects as $object)
						{
							switch($objectClass)
							{
								case 'Addon\Ipam\Api_Subnet':
								{
									if(!C\Tools::is('string&&!empty', $object[Ipam\Api_Subnet::FIELD_NAME])) {
										$object[Ipam\Api_Subnet::FIELD_NAME] = $object['subnet'].'/'.$object['mask'];
									}
									else {
										$object = $this->formatSubnetNameWithIPv($object, true);
									}

									$items[$objectClass::OBJECT_KEY][] = array(
										'name' => $object[$objectClass::FIELD_NAME],
										'subnet' => $object['subnet'],
										'mask' => $object['mask'],
									);
									break;
								}
								default: {
									$items[$objectClass::OBJECT_KEY][] = array('name' => $object[$objectClass::FIELD_NAME]);
								}
							}
						}
					}
					elseif($currentApi instanceof Ipam\Api_Subnet)
					{
						$vlanId = $currentApi->getVlanId();

						if($vlanId !== false)
						{
							$Ipam_Api_Vlan = new Ipam\Api_Vlan($vlanId);
							$vlanNumber = $Ipam_Api_Vlan->getNumber();
							$vlanLabel = $Ipam_Api_Vlan->getName();

							$items[Ipam\Api_Subnet::OBJECT_KEY][] = array(
								'number' => $vlanNumber,
								'name' => $vlanLabel,
							);
						}

						$addresses = $currentApi->getAddresses();

						if($addresses !== false)
						{
							foreach($addresses as $address)
							{
								$items[Ipam\Api_Address::OBJECT_KEY][] = array(
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

			usort($items[Ipam\Api_Section::OBJECT_KEY], $compare);
			usort($items[Ipam\Api_Folder::OBJECT_KEY], $compare);
			usort($items[Ipam\Api_Subnet::OBJECT_KEY], $compare);
			usort($items[Ipam\Api_Vlan::OBJECT_KEY], $compare);
			usort($items[Ipam\Api_Address::OBJECT_KEY], $compare);

			return array(
				'section' => $items[Ipam\Api_Section::OBJECT_KEY],
				'folder' => $items[Ipam\Api_Folder::OBJECT_KEY],
				'subnet' => $items[Ipam\Api_Subnet::OBJECT_KEY],
				'vlan' => $items[Ipam\Api_Vlan::OBJECT_KEY],
				'address' => $items[Ipam\Api_Address::OBJECT_KEY]
			);
		}

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

		public function printSectionInfos(array $args, $fromCurrentPath = true)
		{
			if(isset($args[0]))
			{
				$infos = $this->_getSectionInfos($args[0], $fromCurrentPath);

				if(!$this->_SHELL->isOneShotCall())
				{
					$status = $this->_printInformations('section', $infos);

					if($status === false) {
						$this->_SHELL->error("Section introuvable", 'orange');
					}
				}
				else {
					$this->_RESULTS->setValue($infos);
				}

				return true;
			}

			return false;
		}

		public function printFolderInfos(array $args, $fromCurrentPath = true)
		{
			if(isset($args[0]))
			{
				$infos = $this->_getFolderInfos($args[0], $fromCurrentPath);

				if(!$this->_SHELL->isOneShotCall())
				{
					$status = $this->_printInformations('folder', $infos);

					if($status === false) {
						$this->_SHELL->error("Dossier introuvable", 'orange');
					}
				}
				else {
					$this->_RESULTS->setValue($infos);
				}

				return true;
			}

			return false;
		}

		public function printSubnetInfos(array $args, $fromCurrentPath = true)
		{
			if(isset($args[0]))
			{
				$infos = $this->_getSubnetInfos($args[0], $fromCurrentPath);

				if(!$this->_SHELL->isOneShotCall())
				{
					$status = $this->_printInformations('subnet', $infos);

					if($status === false) {
						$this->_SHELL->error("Subnet introuvable", 'orange');
					}
					else {
						$this->printSubnetExtra($infos);
					}
				}
				else {
					$this->_RESULTS->setValue($infos);
				}

				return true;
			}

			return false;
		}

		public function printVlanInfos(array $args, $fromCurrentPath = true)
		{
			if(isset($args[0]))
			{
				$infos = $this->_getVlanInfos($args[0], $fromCurrentPath);

				if(!$this->_SHELL->isOneShotCall())
				{
					$status = $this->_printInformations('vlan', $infos);

					if($status === false) {
						$this->_SHELL->error("VLAN introuvable", 'orange');
					}
				}
				else {
					$this->_RESULTS->setValue($infos);
				}

				return true;
			}

			return false;
		}

		public function printAddressInfos(array $args, $fromCurrentPath = true)
		{
			if(isset($args[0]))
			{
				$infos = $this->_getAddressInfos($args[0], $fromCurrentPath);

				if(!$this->_SHELL->isOneShotCall())
				{
					$status = $this->_printInformations('address', $infos);

					if($status === false) {
						$this->_SHELL->error("Adresse introuvable", 'orange');
					}
				}
				else {
					$this->_RESULTS->setValue($infos);
				}

				return true;
			}

			return false;
		}

		protected function printSubnetExtra(array $infos)
		{
			if(count($infos) === 1) {
				$path = $infos[0]['path'].'/'.$infos[0]['name'];
				$this->printObjectsList($path);
			}
		}

		protected function _getSectionInfos($section, $fromCurrentPath = true, $path = null)
		{
			$items = array();
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

			foreach($sections as $section)
			{
				$Ipam_Api_Section = new Ipam\Api_Section($section['id']);

				$sectionName = $Ipam_Api_Section->getName();

				$item = array();
				$item['header'] = $sectionName;
				$item['name'] = $sectionName;
				$item['description'] = $Ipam_Api_Section->getDescription();

				$items[] = $item;
			}

			return $items;
		}

		protected function _getFolderInfos($subnet, $fromCurrentPath = true, $path = null)
		{
			// @todo a coder
			return array();
		}

		protected function _getSubnetInfos($subnet, $fromCurrentPath = true, $path = null)
		{
			$items = array();
			$subnets = array();

			$subnet = $this->cleanSubnetNameOfIPv($subnet, $IPv);

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
										$Api_Subnet_Abstract = new Ipam\Api_Subnet($subnet['id']);

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

			foreach($subnets as $subnet)
			{
				$Ipam_Api_Subnet = new Ipam\Api_Subnet($subnet['id']);

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

				if($Ipam_Api_Subnet->isIPv4()) {
					$item['networkIP'] = $Ipam_Api_Subnet->getNetworkIp();
					$item['broadcastIP'] = $Ipam_Api_Subnet->getBroadcastIp();
				}
				elseif($Ipam_Api_Subnet->isIPv6()) {
					$item['firstIP'] = $Ipam_Api_Subnet->getFirstIp();
					$item['lastIP'] = $Ipam_Api_Subnet->getLastIp();
				}

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

		protected function _getVlanInfos($vlan, $fromCurrentPath = true, $path = null)
		{
			$items = array();
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
								$Ipam_Api_Vlan = new Ipam\Api_Vlan($vlan['id']);
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

			foreach($vlans as $vlan)
			{
				$Ipam_Api_Vlan = new Ipam\Api_Vlan($vlan['id']);

				$vlanName = $Ipam_Api_Vlan->getName();
				$vlanNumber = $Ipam_Api_Vlan->getNumber();
				$subnets = $Ipam_Api_Vlan->getSubnets();

				if($subnets !== false)
				{
					$subnets = C\Tools::arrayFilter($subnets, $this->_LIST_FIELDS['vlan']['subnet']['fields']);

					foreach($subnets as &$subnet) {
						$subnet = vsprintf($this->_LIST_FIELDS['vlan']['subnet']['format'], $subnet);
					}
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

		protected function _getAddressInfos($address, $fromCurrentPath = true, $path = null)
		{
			$items = array();
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
								$Ipam_Api_Subnet = new Ipam\Api_Subnet($address['subnetId']);

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

			foreach($addresses as $address)
			{
				$Ipam_Api_Address = new Ipam\Api_Address($address['id']);
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
								foreach($objects['subnets'] as $subnet)
								{
									$text1 = '['.$subnet['sectionName'].']';
									$text1 .= C\Tools::t($text1, "\t", 2, 0, 8);
									$text2 = $subnet['network'].'/'.$subnet['cidrMask'];
									$text2 .= C\Tools::t($text2, "\t", 4, 0, 8);
									$text3 = '{'.$subnet['name'].'}';
									$this->_SHELL->print($text1.$text2.$text3, 'grey');
								}
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
								foreach($objects['vlans'] as $vlan)
								{
									$text1 = '[default]';
									//$text1 = '['.$vlan['domainName'].']';
									$text1 .= C\Tools::t($text1, "\t", 2, 0, 8);
									$text2 = $vlan['number'].' '.$vlan['name'];
									$text2 .= C\Tools::t($text2, "\t", 4, 0, 8);
									$text3 = '{'.$vlan['description'].'}';
									$this->_SHELL->print($text1.$text2.$text3, 'grey');
								}
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
								foreach($objects['addresses'] as $address)
								{
									$text1 = '['.$address['subnetPath'].']';
									$text1 .= C\Tools::t($text1, "\t", 7, 0, 8);
									$text2 = $address['ip'];
									$text2 .= C\Tools::t($text2, "\t", 4, 0, 8);
									$text3 = $address['hostname'].' {'.$address['description'].'}';
									$this->_SHELL->print($text1.$text2.$text3, 'grey');
								}
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
				{
					$subnets = $this->_getSubnetInfos($objectSearch, $this->_searchfromCurrentPath, $path);
					return array('subnets' => $subnets);
					break;
				}
				case 'vlan':
				{
					$vlans = $this->_getVlanInfos($objectSearch, $this->_searchfromCurrentPath, $path);
					return array('vlans' => $vlans);
					break;
				}
				case 'address':
				{
					$addresses = $this->_getAddressInfos($objectSearch, $this->_searchfromCurrentPath, $path);
					return array('addresses' => $addresses);
					break;
				}
				case 'all':
				{
					$subnets = $this->_searchObjects($path, 'subnet', $objectSearch);
					$vlans = $this->_searchObjects($path, 'vlan', $objectSearch);
					$addresses = $this->_searchObjects($path, 'address', $objectSearch);
					return array_merge($subnets, $vlans, $addresses);
					break;
				}
				default: {
					throw new Exception("Search item '".$objectType."' is unknow", E_USER_ERROR);
				}
			}
		}

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

		// CREATE & MODIFY & REMOVE
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
							$Ipam_Api_Address = new Ipam\Api_Address($addresses[0][Ipam\Api_Address::FIELD_ID]);
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
							$Ipam_Api_Address = new Ipam\Api_Address($addresses[0][Ipam\Api_Address::FIELD_ID]);
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
		  * @param Addon\Dcim\Api_Abstract $baseApi
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
						$results = new Ipam\Api_Section($sectionId);
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
						$results = new Ipam\Api_Folder($folderId);
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
							$results = new Ipam\Api_Subnet($subnetId);
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