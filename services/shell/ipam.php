<?php
	include_once('abstract.php');

	class Service_Shell_Ipam extends Service_Shell_Abstract
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


		protected function _getObjects($path = null)
		{
			$items = array(
				'Ipam_Api_Section' => array(),
				'Ipam_Api_Folder' => array(),
				'Ipam_Api_Subnet' => array(),
				'Ipam_Api_Vlan' => array(),
				'Ipam_Api_Address' => array(),
			);

			$currentApi = $this->_browser($path);

			/**
			  * @todo a décommenter après correction bug PHPIPAM
			  * curl -vvv -H "token: [token]" -H "Content-Type: application/json; charset=utf-8" https://ipam.corp.cloudwatt.com/api/myAPP/folders/1185/
			  * Fatal error</b>:  Unsupported operand types in <b>/opt/phpipam/functions/classes/class.Tools.php</b> on line <b>1695</b>
			  */
			$cases = array(
				'Ipam_Api_Section' => array(
					'Ipam_Api_Section' => 'getSubSections',
					//'Ipam_Api_Folder' => 'getFolders',
					'Ipam_Api_Subnet' => 'getSubnets',
				),
				/*'Ipam_Api_Folder' => array(
					'Ipam_Api_Folder' => 'getSubFolders',
					'Ipam_Api_Subnet' => 'getSubnets',
				),*/
				'Ipam_Api_Subnet' => array(
					'Ipam_Api_Subnet' => 'getSubSubnets',
				),
			);

			foreach($cases[get_class($currentApi)] as $objectClass => $objectMethod)
			{
				if($objectMethod !== false) {
					$objects = $currentApi->{$objectMethod}();
				}
				else {
					$objects = false;
				}

				if($objects !== false && count($objects) > 0)
				{
					foreach($objects as $object)
					{
						$objectName = $object[$objectClass::FIELD_NAME];

						switch($objectClass)
						{
							case 'Ipam_Api_Folder':
							case 'Ipam_Api_Subnet':
							{
								$ipv4 = Ipam_Api_Subnet_Abstract::isIpv4Subnet($object);
								$objectName .= ($ipv4) ? ('#IPv4') : ('#IPv6');

								$items[$objectClass][] = array(
									'name' => $objectName,
									'subnet' => $object['subnet'],
									'mask' => $object['mask'],
								);
								break;
							}
							default:
								$items[$objectClass][] = array('name' => $objectName);
						}
					}
				}
				elseif($currentApi instanceof Ipam_Api_Subnet)
				{
					$vlanId = $currentApi->getVlanId();

					if($vlanId !== false)
					{
						$Ipam_Api_Vlan = new Ipam_Api_Vlan($vlanId);
						$vlanNumber = $Ipam_Api_Vlan->getNumber();
						$vlanLabel = $Ipam_Api_Vlan->getName();

						$items['Ipam_Api_Vlan'][] = array(
							'number' => $vlanNumber,
							'name' => $vlanLabel,
						);
					}

					$addresses = $currentApi->getAddresses();

					if($addresses !== false)
					{
						foreach($addresses as $address)
						{
							$items['Ipam_Api_Address'][] = array(
								'ip' => $address['ip'],
								'hostname' => $address['hostname'],
								'description' => $address['description'],
								'status' => ucfirst(Ipam_Api_Address::TAGS[$address['tag']]),
							);
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

			usort($items['Ipam_Api_Section'], $compare);
			usort($items['Ipam_Api_Folder'], $compare);
			usort($items['Ipam_Api_Subnet'], $compare);
			usort($items['Ipam_Api_Vlan'], $compare);
			usort($items['Ipam_Api_Address'], $compare);

			return array(
				'section' => $items['Ipam_Api_Section'],
				'folder' => $items['Ipam_Api_Folder'],
				'subnet' => $items['Ipam_Api_Subnet'],
				'vlan' => $items['Ipam_Api_Vlan'],
				'address' => $items['Ipam_Api_Address']
			);
		}

		public function printObjectInfos(array $args, $fromCurrentPath = true)
		{
			/**
			  * @todo a décommenter après correction bug PHPIPAM
			  * curl -vvv -H "token: [token]" -H "Content-Type: application/json; charset=utf-8" https://ipam.corp.cloudwatt.com/api/myAPP/folders/1185/
			  * Fatal error</b>:  Unsupported operand types in <b>/opt/phpipam/functions/classes/class.Tools.php</b> on line <b>1695</b>
			  */

			// /!\ ls AUB --> On ne doit pas afficher AUB mais le contenu de AUB !
			/*$objectApi = end($this->_pathApi);

			switch(get_class($objectApi))
			{
				case 'Ipam_Api_Section':
					$cases = array(
						'section' => '_getSectionInfos',
						//'folder' => '_getFolderInfos',
						'subnet' => '_getSubnetInfos'
					);
					break;
				case 'Ipam_Api_Folder':
					$cases = array(
						//'folder' => '_getFolderInfos',
						'subnet' => '_getSubnetInfos'
					);
					break;
				case 'Ipam_Api_Subnet':
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

			$result = $this->_printObjectInfos($cases, $args, $fromCurrentPath);

			if($result !== false) {
				list($status, $objectType, $infos) = $result;
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
				$status = $this->_printInformations('section', $infos);

				if($status === false) {
					Tools::e("Section introuvable", 'orange');
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
				$status = $this->_printInformations('folder', $infos);

				if($status === false) {
					Tools::e("Dossier introuvable", 'orange');
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
				$status = $this->_printInformations('subnet', $infos);

				if($status === false) {
					Tools::e("Subnet introuvable", 'orange');
				}
				elseif(count($infos) === 1)
				{
					$this->_MAIN->displayWaitingMsg();

					$path = $infos[0]['path'].'/'.$infos[0]['name'];
					$objects = $this->_getObjects($path);
					$this->_MAIN->deleteWaitingMsg();
					$this->_printObjectsList($objects);
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
				$status = $this->_printInformations('vlan', $infos);

				if($status === false) {
					Tools::e("VLAN introuvable", 'orange');
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
				$status = $this->_printInformations('address', $infos);

				if($status === false) {
					Tools::e("Adresse IP introuvable", 'orange');
				}

				return true;
			}

			return false;
		}

		protected function _getSectionInfos($section, $fromCurrentPath, $path = null)
		{
			$items = array();
			$sections = array();

			if($fromCurrentPath)
			{
				$currentApi = $this->_browser($path);

				if($currentApi instanceof Ipam_Api_Section)
				{
					$sectionId = $currentApi->getSubSectionId($section);

					if(isset($sectionId) && $sectionId !== false) {
						$sections[] = array('id' => $sectionId);
					}
				}
			}
			else
			{
				$sectionNames = Ipam_Api_Section::searchSectionNames($section);

				if(Tools::is('array&&count>0', $sectionNames)) {
					$sections = $sectionNames;
				}
			}

			foreach($sections as $section)
			{
				$Ipam_Api_Section = new Ipam_Api_Section($section['id']);

				$sectionName = $Ipam_Api_Section->getName();

				$item = array();
				$item['header'] = $sectionName;
				$item['name'] = $sectionName;
				$item['description'] = $Ipam_Api_Section->getDescription();

				$items[] = $item;
			}

			return $items;
		}

		protected function _getFolderInfos($subnet, $fromCurrentPath, $path = null)
		{
			// @todo a coder
			return array();
		}

		protected function _getSubnetInfos($subnet, $fromCurrentPath, $path = null)
		{
			$items = array();
			$subnets = array();

			$hasIPv = preg_match('/(#IPv[46])$/i', $subnet, $IPv);

			if($hasIPv) {
				$IPv = (int) substr($IPv[0], -1, 1);
				$subnet = preg_replace('/(#IPv[46])$/i', '', $subnet);
			}
			else {
				$IPv = false;
			}

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

					$cidrSubnets = Ipam_Api_Subnet::searchCidrSubnets($subnet);
					$subnetNames = Ipam_Api_Subnet::searchSubnetNames($subnet);

					foreach(array($cidrSubnets, $subnetNames) as $_subnets)
					{
						if(Tools::is('array&&count>0', $_subnets))
						{
							foreach($_subnets as $subnet)
							{
								if($subnet['sectionId'] === (string) $currentSectionId)
								{
									if(isset($currentSubnetId))
									{
										$Ipam_Api_Subnet = new Ipam_Api_Subnet($subnet['id']);

										do
										{
											if($Ipam_Api_Subnet->getSubnetId() === $currentSubnetId) {
												break;
											}
										}
										while(($Ipam_Api_Subnet = $Ipam_Api_Subnet->subnetApi) !== false);

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
				
				$cidrSubnets = Ipam_Api_Subnet::searchCidrSubnets($subnet);
				$subnetNames = Ipam_Api_Subnet::searchSubnetNames($subnet);

				foreach(array($cidrSubnets, $subnetNames) as $_subnets)
				{
					if(Tools::is('array&&count>0', $_subnets)) {
						$subnets = array_merge($subnets, $_subnets);
					}
				}
			}

			foreach($subnets as $subnet)
			{
				$Ipam_Api_Subnet = new Ipam_Api_Subnet($subnet['id']);

				if($IPv !== false && !$Ipam_Api_Subnet->{'isIPv'.$IPv}()) {
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
				$item['path'] = '/'.implode('/', $Ipam_Api_Subnet->getPath());

				$item['usage'] = '';
				$subnetUsage = $Ipam_Api_Subnet->getUsage();

				foreach($subnetUsage as $fieldName => $fieldValue)
				{
					switch($fieldName)
					{
						case Ipam_Api_Subnet::USAGE_FIELDS['used']:
						case Ipam_Api_Subnet::USAGE_FIELDS['total']:
						case Ipam_Api_Subnet::USAGE_FIELDS['free']: {
							$item['usage'] .= ucwords($fieldName).': '.$fieldValue.' | ';
							break;
						}
					}
				}

				$items[] = $item;
			}

			return $items;
		}

		protected function _getVlanInfos($vlan, $fromCurrentPath, $path = null)
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

					$vlanNumbers = Ipam_Api_Vlan::searchVlanNumbers($vlan);
					$vlanNames = Ipam_Api_Vlan::searchVlanNames($vlan);

					foreach(array($vlanNumbers, $vlanNames) as $_vlans)
					{
						if(Tools::is('array&&count>0', $_vlans))
						{
							foreach($_vlans as $vlan)
							{
								$Ipam_Api_Vlan = new Ipam_Api_Vlan($vlan['id']);
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
				$vlanNumbers = Ipam_Api_Vlan::searchVlanNumbers($vlan);
				$vlanNames = Ipam_Api_Vlan::searchVlanNames($vlan);

				foreach(array($vlanNumbers, $vlanNames) as $_vlans)
				{
					if(Tools::is('array&&count>0', $_vlans)) {
						$vlans = array_merge($vlans, $_vlans);
					}
				}
			}

			foreach($vlans as $vlan)
			{
				$Ipam_Api_Vlan = new Ipam_Api_Vlan($vlan['id']);

				$vlanName = $Ipam_Api_Vlan->getName();
				$vlanNumber = $Ipam_Api_Vlan->getNumber();
				$subnets = $Ipam_Api_Vlan->getSubnets();

				$subnets = $this->_arrayFilter($subnets, $this->_LIST_FIELDS['vlan']['subnet']['fields']);

				foreach($subnets as &$subnet) {
					$subnet = vsprintf($this->_LIST_FIELDS['vlan']['subnet']['format'], $subnet);
				}

				$item = array();
				$item['header'] = $vlanNumber.' '.$vlanName;
				$item['name'] = $vlanName;
				$item['number'] = $vlanNumber;
				$item['description'] = $Ipam_Api_Vlan->getDescription();
				$item['subnets'] = $subnets;

				$items[] = $item;
			}

			return $items;
		}

		protected function _getAddressInfos($address, $fromCurrentPath, $path = null)
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

					$addressIps = Ipam_Api_Address::searchIpAddresses($address);
					$addressNames = Ipam_Api_Address::searchAddressNames($address);
					$addressDescs = Ipam_Api_Address::searchAddressDescs($address);

					foreach(array($addressIps, $addressNames, $addressDescs) as $_addresses)
					{
						if(Tools::is('array&&count>0', $_addresses))
						{
							foreach($_addresses as $address)
							{
								$Ipam_Api_Subnet = new Ipam_Api_Subnet($address['subnetId']);

								if($Ipam_Api_Subnet->getSectionId() === (string) $currentSectionId)
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
				$addressIps = Ipam_Api_Address::searchIpAddresses($address);
				$addressNames = Ipam_Api_Address::searchAddressNames($address);
				$addressDescs = Ipam_Api_Address::searchAddressDescs($address);

				foreach(array($addressIps, $addressNames, $addressDescs) as $_addresses)
				{
					if(Tools::is('array&&count>0', $_addresses)) {
						$addresses = array_merge($addresses, $_addresses);
					}
				}
			}

			foreach($addresses as $address)
			{
				$Ipam_Api_Address = new Ipam_Api_Address($address['id']);
				$Ipam_Api_Subnet = $Ipam_Api_Address->getSubnetApi();

				$ip = $Ipam_Api_Address->getIp();
				$cidrMask = $Ipam_Api_Subnet->getCidrMask();

				$item = array();
				$item['header'] = $ip.'/'.$cidrMask;
				$item['ip'] = $ip;
				$item['cidrMask'] = $cidrMask;
				$item['netMask'] = $Ipam_Api_Subnet->getNetMask();
				$item['subnet'] = $Ipam_Api_Subnet->getSubnet();
				$item['gateway'] = $Ipam_Api_Subnet->getGateway();
				$item['vlanNumber'] = $Ipam_Api_Subnet->vlanApi->getNumber();
				$item['VlanName'] = $Ipam_Api_Subnet->vlanApi->getName();
				$item['hostname'] = $Ipam_Api_Address->getHostname();
				$item['description'] = $Ipam_Api_Address->getDescription();
				$item['status'] = ucfirst(Ipam_Api_Address::TAGS[$Ipam_Api_Address->getTag()]);
				$item['note'] = $Ipam_Api_Address->getNote();
				$item['subnetPath'] = '/'.implode('/', $Ipam_Api_Subnet->getPath());

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

				$this->_MAIN->deleteWaitingMsg();

				if($objects !== false)
				{
					$this->_MAIN->setLastCmdResult($objects);
					$this->_MAIN->e(PHP_EOL.'RECHERCHE ('.round($time2-$time1).'s)', 'black', 'white', 'bold');

					if(!$this->_MAIN->isOneShotCall())
					{
						if(isset($objects['subnets']))
						{
							$this->_MAIN->e(PHP_EOL);

							$counter = count($objects['subnets']);
							$this->_MAIN->e(PHP_EOL.'SUBNETS ('.$counter.')', 'black', 'white');

							if($counter > 0)
							{
								foreach($objects['subnets'] as $subnet)
								{
									$text1 = '['.$subnet['sectionName'].']';
									$text1 .= Tools::t($text1, "\t", 2, 0, 8);
									$text2 = $subnet['network'].'/'.$subnet['cidrMask'];
									$text2 .= Tools::t($text2, "\t", 4, 0, 8);
									$text3 = '{'.$subnet['name'].'}';
									Tools::e(PHP_EOL.$text1.$text2.$text3, 'grey');
								}
							}
							else {
								Tools::e(PHP_EOL.'Aucun résultat', 'orange');
							}
						}

						// @todo gerer l2domains
						if(isset($objects['vlans']))
						{
							$this->_MAIN->e(PHP_EOL);

							$counter = count($objects['vlans']);
							$this->_MAIN->e(PHP_EOL.'VLANS ('.$counter.')', 'black', 'white');

							if($counter > 0)
							{
								foreach($objects['vlans'] as $vlan)
								{
									$text1 = '[default]';
									//$text1 = '['.$vlan['domainName'].']';
									$text1 .= Tools::t($text1, "\t", 2, 0, 8);
									$text2 = $vlan['number'].' '.$vlan['name'];
									$text2 .= Tools::t($text2, "\t", 4, 0, 8);
									$text3 = '{'.$vlan['description'].'}';
									Tools::e(PHP_EOL.$text1.$text2.$text3, 'grey');
								}
							}
							else {
								Tools::e(PHP_EOL.'Aucun résultat', 'orange');
							}
						}

						if(isset($objects['addresses']))
						{
							$this->_MAIN->e(PHP_EOL);

							$counter = count($objects['addresses']);
							$this->_MAIN->e(PHP_EOL.'ADDRESSES ('.$counter.')', 'black', 'white');

							if($counter > 0)
							{
								foreach($objects['addresses'] as $address)
								{
									$text1 = '['.$address['subnetPath'].']';
									$text1 .= Tools::t($text1, "\t", 7, 0, 8);
									$text2 = $address['ip'];
									$text2 .= Tools::t($text2, "\t", 4, 0, 8);
									$text3 = $address['hostname'].' {'.$address['description'].'}';
									Tools::e(PHP_EOL.$text1.$text2.$text3, 'grey');
								}
							}
							else {
								Tools::e(PHP_EOL.'Aucun résultat', 'orange');
							}
						}
					}
				}
				else {
					Tools::e("Aucun résultat", 'orange');
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
			$lastSectionApi = $this->_getLastApiPath($pathApi, 'Ipam_Api_Section');
			// /!\ La toute 1ere section n'existe pas, voir Service_Ipam::_moveToRoot
			return ($lastSectionApi !== false && $lastSectionApi->sectionExists()) ? ($lastSectionApi) : (false);
		}

		protected function _getLastSubnetPath(array $pathApi)
		{
			return $this->_getLastApiPath($pathApi, 'Ipam_Api_Subnet');
		}
	}