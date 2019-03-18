<?php
	namespace Addon\Ipam;

	use ArrayObject;

	use Core as C;
	use Core\Exception as E;

	class Connector_Rest extends Connector_Abstract
	{
		const REST_URN = array(
			'user' => 'user',
			'sections' => 'sections',
			'cwSections' => 'cw_sections',
			'subnets' => 'subnets',
			'cwSubnets' => 'cw_subnets',
			'folders' => 'folders',
			'addresses' => 'addresses',
			'cwAddresses' => 'cw_addresses',
			'vlans' => 'vlans',
			'cwVlans' => 'cw_vlans',
			'l2domains' => 'l2domains',
			'vrfs' => 'vrfs',
			'tools' => 'tools',
			'prefix' => 'prefix',
		);

		const SECTION_ROOT_ID = '0';
		const FOLDER_ROOT_ID = '0';
		const SUBNET_ROOT_ID = '0';
		const SUBNET_IS_FOLDER = '1';
		const SUBNET_IS_NOT_FOLDER = '0';

		const ADDRESS_TAGS = array(
			'offline' => 1,
			'online' => 2,
			'reserved' => 3,
			'DHCP' => 4,
		);

		/**
		  * @var string
		  */
		protected $_id;

		/**
		  * IPAM configuration
		  * @var Core\Config
		  */
		protected $_config;

		/**
		  * @var string
		  */
		protected $_server;

		/**
		  * @var string
		  */
		protected $_application;

		/**
		  * Core\Rest API
		  * @var \ArrayObject
		  */
		protected $_restAPI;

		/**
		  * @var bool
		  */
		protected $_debug = false;


		public function __construct($id, $server, $application, $login, $password, $printInfoMessages = true, $debug = false)
		{
			/**
			  * Pourra servir plus tard pour sélectionner une configuration
			  * différente en fonction de l'ID à partir de CONFIG
			  */
			$this->_id = $id;
			$this->debug($debug);

			$this->_config = C\Config::getInstance()->IPAM;

			if($printInfoMessages) {
				C\Tools::e(PHP_EOL."Connection HTTP à l'IPAM @ ".$server." veuillez patienter ... ", 'blue');
			}

			$this->_server = rtrim($server, '/');
			$this->_application = $application;
			$this->_restAPI = new ArrayObject();

			$httpProxy = getenv('http_proxy');
			$httpsProxy = getenv('https_proxy');

			$this->_initRestAPI('user', $this->_server, $this->_application, self::REST_URN['user'], $httpProxy, $httpsProxy);
			$this->_restAPI->user->setHttpAuthCredentials($login, $password);
			$response = $this->_restAPI->user->post();
			$response = $this->_getCallResponse($response);
			$ipamApiToken = $response['token'];

			// Exécuter ce qui suit même pour user!
			foreach(self::REST_URN as $key => $urn) {
				$this->_initRestAPI($key, $this->_server, $this->_application, $urn, $httpProxy, $httpsProxy);
				$this->_restAPI->{$key}->addHeader('token: '.$ipamApiToken);
			}

			if($printInfoMessages) {
				C\Tools::e("[OK]", 'green');
			}
		}

		protected function _initRestAPI($key, $server, $application, $urn, $httpProxy, $httpsProxy)
		{
			$server = $server.'/api/'.$application.'/'.$urn;
			$this->_restAPI->{$key} = new C\Rest($server, 'IPAM_'.$key, $this->_debug);

			$this->_restAPI->{$key}
					->setOpt(CURLOPT_HEADER, false)
					->setOpt(CURLOPT_RETURNTRANSFER, true)
					->setOpt(CURLOPT_FOLLOWLOCATION, true)
					->addHeader('Content-Type: application/json');

			switch(substr($server, 0, 6))
			{
				case 'http:/':
					if(C\Tools::is('string&&!empty', $httpProxy))
					{
						$this->_restAPI->{$key}
								//->setHttpAuthMethods(true)	// NE PAS UTILISER EN HTTP!
								->setOpt(CURLOPT_HTTPPROXYTUNNEL, true)
								->setOpt(CURLOPT_PROXYTYPE, CURLPROXY_HTTP)
								->setOpt(CURLOPT_PROXY, $httpProxy);
					}
					break;
				case 'https:':
					if(C\Tools::is('string&&!empty', $httpsProxy))
					{
						$this->_restAPI->{$key}
								->setHttpAuthMethods(true)
								->setOpt(CURLOPT_HTTPPROXYTUNNEL, true)
								->setOpt(CURLOPT_PROXYTYPE, CURLPROXY_HTTP)
								->setOpt(CURLOPT_PROXY, $httpsProxy);
					}
					break;
				default:
					throw new Exception("L'adresse du serveur IPAM doit commencer par http ou https", E_USER_ERROR);
			}
		}

		public function getServerId()
		{
			return $this->_id;
		}

		public function getServerUrl()
		{
			return preg_replace('#^(http(s)?://)#i', '', $this->_server);
		}

		public function getServerAdd()
		{
			$server = $this->getServerUrl;
			$serverParts = explode('/', $server, 2);
			return current($serverParts);
		}

		public function getWebUrl()
		{
			return $this->_server;
		}

		protected function _getCallResponse($json)
		{
			$response = json_decode($json, true);

			if($this->_isValidResponse($response))
			{
				if(array_key_exists('data', $response)) {
					return $response['data'];
				}
				elseif(array_key_exists('message', $response)) {
					return $response['message'];
				}
			}

			return false;
		}

		protected function _isValidResponse($response)
		{
			return (!$this->_isEmptyResponse($response) && !$this->_isErrorResponse($response));
		}

		protected function _isEmptyResponse($response)
		{
			return (C\Tools::is('string&&empty', $response) || C\Tools::is('array&&count==0', $response));
		}

		protected function _isErrorResponse($response)
		{
			return (
				!is_array($response) || (array_key_exists('success', $response) && $response['success'] !== true) ||
				!array_key_exists('code', $response) || !($response['code'] >= 200 && $response['code'] <= 299)
			);
		}

		public function resolvToLabel($objectType, $objectId)
		{
			switch($objectType)
			{
				case Api_Section::OBJECT_TYPE:
					$restAPI = $this->_restAPI->sections;
					$fieldName = Api_Section::FIELD_NAME;
					break;
				case Api_Folder::OBJECT_TYPE:
					$restAPI = $this->_restAPI->folders;
					$fieldName = Api_Folder::FIELD_NAME;
					break;
				case Api_Subnet::OBJECT_TYPE:
					$restAPI = $this->_restAPI->subnets;
					$fieldName = Api_Subnet::FIELD_NAME;
					break;
				case Api_Vlan::OBJECT_TYPE:
					$restAPI = $this->_restAPI->vlans;
					$fieldName = Api_Vlan::FIELD_NAME;
					break;
				case Api_Address::OBJECT_TYPE:
					$restAPI = $this->_restAPI->addresses;
					$fieldName = Api_Address::FIELD_NAME;
					break;
				default:
					throw new Exception("Cet object n'est pas d'un type valide", E_USER_ERROR);
			}

			$result = $restAPI->{$objectId}->get();
			$response = $this->_getCallResponse($result);

			return ($response !== false) ? ($response[$fieldName]) : (false);
		}

		protected function _apiGetEquipPort($equipLabel, $portLabel = null, $IPv = 4)
		{
			$args = array();
			$args['description'] = $equipLabel;
			if($portLabel !== null) $args['port'] = $portLabel;
			if($IPv !== null) $args['ip_version'] = $IPv;

			$addresses = $this->_restAPI->cwAddresses->search->get($args);
			$addresses = $this->_getCallResponse($addresses);

			if($addresses === false) {
				return array();
			}

			$vars = array();

			foreach($addresses as $address)
			{
				//SUBNET
				$subnetId = $address['subnetId'];
				$subnet = $this->_restAPI->subnets->{$subnetId}->get();
				$subnet = $this->_getCallResponse($subnet);

				if($subnet !== false)
				{
					$netMask = Tools::cidrMaskToNetMask($subnet['mask']);

					$vars[] = array(
						'portName' => $address['port'],
						'address' => $address['ip'],
						'netMask' => $netMask,
						'cidrMask' => $subnet['mask'],
						'subnetId' => (int) $subnetId,
						'__vlanId__' => $subnet['vlanId'],
					);
				}
			}

			return $vars;
		}

		protected function _apiGetEquipVlan($equipLabel, $portLabel = null, $IPv = 4)
		{
			$vars = array();
			$addresses = $this->_apiGetEquipPort($equipLabel, $portLabel, $IPv);

			foreach($addresses as $address)
			{
				//VLAN
				$vlanId = $address['__vlanId__'];

				if(C\Tools::is('int&&>0', $vlanId))
				{
					$vlan = $this->_restAPI->vlans->{$vlanId}->get();
					$vlan = $this->_getCallResponse($vlan);

					if($vlan !== false) {
						$address['vlanId'] = $vlan['number'];
						$address['vlanName'] = $vlan['name'];
						unset($address['__vlanId__']);
						$vars[] = $address;
					}
				}
			}

			return $vars;
		}

		public function getByEquipLabel($equipLabel, $IPv = 4)
		{
			return $this->_apiGetEquipVlan($equipLabel, '##present##', $IPv);
		}

		public function getByEquipLabelVlanName($equipLabel, $vlanName, $IPv = 4)
		{
			$vlanName = preg_quote($vlanName, '#');
			$vlanName = str_replace('\\*', '.*', $vlanName);

			return $this->getByEquipLabelVlanRegex($equipLabel, $vlanName, $IPv);
		}

		public function getByEquipLabelVlanRegex($equipLabel, $vlanRegex, $IPv = 4)
		{
			$items = $this->_apiGetEquipVlan($equipLabel, null, $IPv);

			foreach($items as $item)
			{
				if(preg_match('#'.$vlanRegex.'#i', $item['vlanName'])) {
					return $item;
				}
			}

			return false;
		}

		public function getByEquipLabelPortLabel($equipLabel, $portLabel, $IPv = 4)
		{
			$items = $this->_apiGetEquipVlan($equipLabel, $portLabel, $IPv);
			return (count($items) > 0) ? ($items[0]) : (false);
		}

		public function getGatewayBySubnetId($subnetId)
		{
			if(is_array($subnetId) && array_key_exists('subnetId', $subnetId)) {
				$subnetId = $subnetId['subnetId'];
			}

			if(C\Tools::is('int&&>0', $subnetId))
			{
				/*$items = $this->_restAPI->subnets->{$subnetId}->addresses->get();
				$items = $this->_getCallResponse($items);

				foreach($items as $item)
				{
					// /!\ Return string, no int
					if($item['is_gateway'] === "1") {
						return $item['ip'];
					}
				}*/

				$subnet = $this->_restAPI->subnets->{$subnetId}->get();
				$subnet = $this->_getCallResponse($subnet);

				if(isset($subnet['gateway']['ip_addr'])) {
					return $subnet['gateway']['ip_addr'];
				}
			}

			return false;
		}

		public function getVlanNamesByVlanIds(array $vlanIds, array $environments)
		{
			$vlanNames = array();

			foreach($vlanIds as $vlanId)
			{
				$items = $this->_restAPI->vlans->search->{$vlanId}->get();
				$items = $this->_getCallResponse($items);

				foreach($items as $item)
				{
					$vlanName = $item['name'];

					foreach($environments as $environment)
					{
						if(preg_match('#^('.preg_quote($environment, "#").')[-_]#i', $vlanName)) {
							$vlanNames[$vlanName] = $vlanId;
							break(2);
						}
					}
				}
			}

			return $vlanNames;
		}

		public function getMcLagRowset($hostName, $portName, $IPv = 4)
		{
			$rowset = array();
			$addresses = $this->_apiGetEquipPort($hostName, $portName, $IPv);

			foreach($addresses as $address)
			{
				$subnet = $this->_restAPI->subnets->{$address['subnetId']}->get();
				$subnet = $this->_getCallResponse($subnet);

				if(preg_match('#iccp#i', $subnet['description'])) {
					$mcLagSubnet = $subnet;
					break;
				}
			}

			if(isset($mcLagSubnet))
			{
				$equipments = $this->_restAPI->subnets->{$mcLagSubnet['id']}->addresses->get();
				$equipments = $this->_getCallResponse($equipments);

				foreach($equipments as $equipment)
				{
					$netMask = Tools::cidrMaskToNetMask($mcLagSubnet['mask']);

					$rowset[] = array(
						'hostName' => $equipment['description'],
						'iccp' => $equipment['ip'], 'address' => $equipment['ip'],
						'cidrMask' => $mcLagSubnet['mask'], 'netMask' => $netMask,
					);
				}
			}

			return $rowset;
		}

		/**
		  * /!\ Dans l'IPAM le champ is_gateway peut être à TRUE pour seulement une entrée par subnet
		  * On ne peut donc pas avoir deux VIP VRRP dans le même subnet retournées si on test ce champ
		  **/
		public function getVrrpRowset($hostName, $subnetId, $IPv = 4)
		{
			$args = array();
			$args['description'] = '##like##'.substr($hostName, 0, 17).'%';
			$args['note'] = '##like##%vrrp%';

			if(C\Tools::is('int&&>=0', $subnetId)) {
				$args['subnetId'] = $subnetId;
			}

			if($IPv === 4 || $IPv === 6) {
				$args['ip_version'] = $IPv;
			}

			$addresses = $this->_restAPI->cwAddresses->search->get($args);
			$addresses = $this->_getCallResponse($addresses);

			if($addresses === false) {
				return array();
			}

			$rowset = array();

			foreach($addresses as $address)
			{
				$subnetId = $address['subnetId'];
				$subnet = $this->_restAPI->subnets->{$subnetId}->get();
				$subnet = $this->_getCallResponse($subnet);

				if($subnet !== false)
				{
					$netMask = Tools::cidrMaskToNetMask($subnet['mask']);

					$rowset[] = array(
						'note' => $address['note'],
						'address' => $address['ip'],
						'netMask' => $netMask,
						'cidrMask' => $subnet['mask'],
					);
				}
			}

			return $rowset;
		}

		public function getAllSections()
		{
			$sections = $this->_restAPI->sections->get();
			return $this->_getCallResponse($sections);
		}

		public function getRootSections()
		{
			return $this->_getSections(self::SECTION_ROOT_ID);
		}

		public function getSections($sectionId = null)
		{
			if(C\Tools::is('int&&>=0', $sectionId)) {
				return $this->_getSections($sectionId);
			}
			else {
				return false;
			}
		}

		/*protected function _getSections($sectionId = null)
		{
			if(C\Tools::is('int&&>=0', $sectionId))
			{
				$sections = $this->_restAPI->sections->get();
				$sections = $this->_getCallResponse($sections);

				if($sections !== false)
				{
					$subSections = array();

					foreach($sections as $section)
					{
						if($section['masterSection'] === $sectionId) {
							$subSections[] = $section;
						}
					}

					return $subSections;
				}
			}

			return false;
		}*/
		
		protected function _getSections($sectionId = null)
		{
			if(C\Tools::is('int&&>=0', $sectionId))
			{
				$args = array();
				$args['masterSection'] = $sectionId;

				$sections = $this->_restAPI->cwSections->search->get($args);
				return $this->_getCallResponse($sections);
			}
			else {
				return false;
			}
		}

		public function getSection($sectionId)
		{
			if(C\Tools::is('int&&>0', $sectionId))
			{
				$section = $this->_restAPI->sections->{$sectionId}->get();
				return $this->_getCallResponse($section);
			}
			else {
				return false;
			}
		}

		public function getAllFolders()
		{
			$folders = $this->_restAPI->folders->all->get();
			$folders = $this->_getCallResponse($folders);

			if($folders !== false)
			{
				foreach($folders as $index => $folder)
				{
					/**
					  * L'API IPAM retourne des subnets
					  * @todo corriger l'API ou passer par un controleur custom
					  */
					if($folder['isFolder'] === self::SUBNET_IS_NOT_FOLDER) {
						unset($folders[$index]);
					}
				}

				return $folders;
			}
			else {
				return array();
			}
		}

		public function getRootFolders($sectionId)
		{
			/**
			  * @todo a décommenter après correction bug PHPIPAM
			  * curl -vvv -H "token: [token]" -H "Content-Type: application/json; charset=utf-8" https://ipam.corp.cloudwatt.com/api/myAPP/folders/1185/
			  * Fatal error</b>:  Unsupported operand types in <b>/opt/phpipam/functions/classes/class.Tools.php</b> on line <b>1695</b>
			array(30) {
				'id' =>
				string(4) "1185"
				'subnet' =>
				string(7) "0.0.0.0"
				'mask' =>
				string(0) ""
				'sectionId' =>
				string(2) "17"
				'description' =>
				string(11) "Integration"
				'firewallAddressObject' =>
				NULL
				'vrfId' =>
				string(1) "0"
				'masterSubnetId' =>
				string(1) "0"
				...
			)
			  */
			return $this->_getSubnets($sectionId, false, true);
			//return $this->_getSubnets($sectionId, self::FOLDER_ROOT_ID, true);
		}

		public function getFolders($sectionId, $folderId = false)
		{
			return $this->_getSubnets($sectionId, $folderId, true);
		}

		public function getFolder($folderId)
		{
			return $this->_getSubnet($folderId, true);
		}

		public function getAllSubnets()
		{
			$subnets = $this->_restAPI->subnets->all->get();
			$subnets = $this->_getCallResponse($subnets);

			if($subnets !== false)
			{
				foreach($subnets as $index => $subnet)
				{
					/**
					  * L'API IPAM retourne des dossiers et possiblement des subnets invalides
					  * @todo corriger l'API ou passer par un controleur custom
					  *
					  * subnet: null, ''
					  * mask: null, ''
					  */
					if($subnet['isFolder'] === self::SUBNET_IS_FOLDER || empty($subnet['subnet']) ||
						!($subnet['mask'] >= 0 && $subnet['mask'] <= 128))
					{
						unset($subnets[$index]);
					}
				}

				return $subnets;
			}
			else {
				return array();
			}
		}

		public function getRootSubnets($sectionId)
		{
			/**
			  * @todo a décommenter après correction bug PHPIPAM
			  * curl -vvv -H "token: [token]" -H "Content-Type: application/json; charset=utf-8" https://ipam.corp.cloudwatt.com/api/myAPP/folders/1185/
			  * Fatal error</b>:  Unsupported operand types in <b>/opt/phpipam/functions/classes/class.Tools.php</b> on line <b>1695</b>
			array(30) {
				'id' =>
				string(4) "1185"
				'subnet' =>
				string(7) "0.0.0.0"
				'mask' =>
				string(0) ""
				'sectionId' =>
				string(2) "17"
				'description' =>
				string(11) "Integration"
				'firewallAddressObject' =>
				NULL
				'vrfId' =>
				string(1) "0"
				'masterSubnetId' =>
				string(1) "0"
				...
			)
			  */
			return $this->_getSubnets($sectionId, false, false);
			//return $this->_getSubnets($sectionId, self::SECTION_ROOT_ID, false);
		}

		public function getSubnets($sectionId, $subnetId = false)
		{
			return $this->_getSubnets($sectionId, $subnetId, false);
		}

		public function getSubnet($subnetId)
		{
			return $this->_getSubnet($subnetId, false);
		}

		protected function _getSubnets($sectionId, $subnetId, $isFolder)
		{
			if(C\Tools::is('int&&>0', $sectionId) && ($subnetId === false || C\Tools::is('int&&>=0', $subnetId)))
			{
				$subnets = $this->_restAPI->sections->{$sectionId}->subnets->get();
				$subnets = $this->_getCallResponse($subnets);

				if($subnets !== false)
				{
					if($subnetId === false && $isFolder === null) {
						return $subnets;
					}
					else
					{
						$subSubnets = array();

						foreach($subnets as $subnet)
						{
							if(($subnetId === false || (int) $subnet['masterSubnetId'] === $subnetId) && (bool) $subnet['isFolder'] === $isFolder) {
								$subSubnets[] = $subnet;
							}
						}

						return $subSubnets;
					}
				}
			}

			return false;
		}

		protected function _getSubnet($subnetId, $isFolder = false)
		{
			if(C\Tools::is('int&&>0', $subnetId))
			{
				if($isFolder === true) {
					$subnet = $this->_restAPI->folders->{$subnetId}->get();
				}
				else {
					$subnet = $this->_restAPI->subnets->{$subnetId}->get();
				}

				return $this->_getCallResponse($subnet);
			}
			else {
				return false;
			}
		}

		public function getSubnetUsage($subnetId)
		{
			if(C\Tools::is('int&&>0', $subnetId))
			{
				$subnetUsage = $this->_restAPI->subnets->{$subnetId}->usage->get();
				return $this->_getCallResponse($subnetUsage);
			}
			else {
				return false;
			}
		}

		public function getAllVlans()
		{
			$vlans = $this->_restAPI->vlans->get();
			return $this->_getCallResponse($vlans);
		}

		public function getVlan($vlanId)
		{
			if(C\Tools::is('int&&>0', $vlanId))
			{
				$vlan = $this->_restAPI->vlans->{$vlanId}->get();
				return $this->_getCallResponse($vlan);
			}
			else {
				return false;
			}
		}

		public function getSubnetsFromVlan($vlanId, $sectionId = false)
		{
			if(C\Tools::is('int&&>0', $vlanId))
			{
				$restAPI = $this->_restAPI->vlans->{$vlanId}->subnets;

				if(C\Tools::is('int&&>0', $sectionId)) {
					$restAPI = $restAPI->{$sectionId};
				}

				$subnets = $restAPI->get();
				return $this->_getCallResponse($subnets);
			}
			else {
				return false;
			}
		}

		public function getAddresses($subnetId)
		{
			if(C\Tools::is('int&&>0', $subnetId))
			{
				$addresses = $this->_restAPI->subnets->{$subnetId}->addresses->get();
				return $this->_getCallResponse($addresses);
			}
			else {
				return false;
			}
		}

		public function getAddress($id, $ip = null)
		{
			if(C\Tools::is('int&&>0', $id))
			{
				if($ip === null) {
					$address = $this->_restAPI->addresses->{$id}->get();
					return $this->_getCallResponse($address);
				}
				elseif(Tools::isIP($ip))
				{
					$address = $this->_restAPI->subnets->{$id}->addresses->{$ip}->get();
					$address = $this->_getCallResponse($address);

					if($address != false)
					{
						if(count($address) === 1) {
							return current($address);
						}
						else {
							throw new Exception("Multiple addresses returned", E_USER_ERROR);
						}
					}
				}
			}

			return false;
		}

		public function searchSectionName($sectionName, $sectionId = null, $strict = false)
		{
			if(C\Tools::is('string&&!empty', $sectionName))
			{
				$args = array();

				if($strict) {
					$args['name'] = $sectionName;
				}
				else {
					$sectionName = rtrim($sectionName, '*%');
					$sectionName = str_replace('*', '%', $sectionName);
					$args['name'] = '##like##'.$sectionName.'%';
				}

				// Search in section or not
				if(C\Tools::is('int&&>=0', $sectionId)) {
					$args['masterSection'] = $sectionId;		// @todo passer en subnetId dans Cw_sections.php
				}

				$sections = $this->_restAPI->cwSections->search->get($args);
				return $this->_getCallResponse($sections);
			}
			else {
				return false;
			}
		}

		public function searchFolders($cidrSubnet)
		{
			return $this->_searchSubnets($cidrSubnet, true);
		}

		public function searchSubnets($cidrSubnet)
		{
			return $this->_searchSubnets($cidrSubnet, false);
		}

		protected function _searchSubnets($cidrSubnet, $isFolder = false)
		{
			if(Tools::isSubnet($cidrSubnet))
			{
				if($isFolder) {
					$restApi = $this->_restAPI->folders;
				}
				else {
					$restApi = $this->_restAPI->subnets;
				}

				$cidrSubnet = explode('/', $cidrSubnet, 2);
				$subnetRestApi = $restApi->cidr;

				foreach($cidrSubnet as $subnetPart) {
					$subnetRestApi->{$subnetPart};
				}

				$subnets = $subnetRestApi->get();
				return $this->_getCallResponse($subnets);
			}
			else {
				return false;
			}
		}

		// $strict for future use
		public function searchSubnetCidr($subnetCidr, $subnetId = null, $sectionId = null, $strict = false)
		{
			if(Tools::isSubnet($subnetCidr))
			{
				$args = array();

				list($args['subnet'], $args['mask']) = explode('/', $subnetCidr, 2);

				// Search in root or subnet
				if(C\Tools::is('int&&>=0', $subnetId)) {
					$args['subnetId'] = $subnetId;
				}

				// Search in section or not
				if(C\Tools::is('int&&>=0', $sectionId)) {
					$args['sectionId'] = $sectionId;
				}

				$subnets = $this->_restAPI->cwSubnets->search->get($args);
				return $this->_getCallResponse($subnets);
			}
			else {
				return false;
			}
		}

		public function searchFolderName($folderName, $subnetId = null, $sectionId = null, $strict = false)
		{
			return $this->_searchSubnetName($folderName, null, $subnetId, $sectionId, $strict, true);
		}

		public function searchSubnetName($subnetName, $IPv = null, $subnetId = null, $sectionId = null, $strict = false)
		{
			return $this->_searchSubnetName($subnetName, $IPv, $subnetId, $sectionId, $strict, false);
		}

		protected function _searchSubnetName($subnetName, $IPv = null, $subnetId = null, $sectionId = null, $strict = false, $isFolder = false)
		{
			if(C\Tools::is('string&&!empty', $subnetName))
			{
				$args = array();

				if($strict) {
					$args['description'] = $subnetName;
				}
				else {
					$subnetName = rtrim($subnetName, '*%');
					$subnetName = str_replace('*', '%', $subnetName);
					$args['description'] = '##like##'.$subnetName.'%';
				}

				if(($IPv === 4 || $IPv === 6) && !$isFolder) {
					$args['ip_version'] = $IPv;
				}

				// Search in root or subnet
				if(C\Tools::is('int&&>=0', $subnetId)) {
					$args['subnetId'] = $subnetId;
				}

				// Search in section or not
				if(C\Tools::is('int&&>=0', $sectionId)) {
					$args['sectionId'] = $sectionId;
				}

				$args['isFolder'] = ($isFolder) ? (1) : (0);

				$subnets = $this->_restAPI->cwSubnets->search->get($args);
				return $this->_getCallResponse($subnets);
			}
			else {
				return false;
			}
		}

		public function searchVlans($vlanNumber)
		{
			if(C\Tools::is('int&&>0', $vlanNumber)) {
				$vlans = $this->_restAPI->vlans->search->{$vlanNumber}->get();
				return $this->_getCallResponse($vlans);
			}
			else {
				return false;
			}
		}

		public function searchVlanName($vlanName, $strict = false)
		{
			if(C\Tools::is('string&&!empty', $vlanName))
			{
				$args = array();

				if($strict) {
					$args['name'] = $vlanName;
				}
				else {
					$vlanName = rtrim($vlanName, '*%');
					$vlanName = str_replace('*', '%', $vlanName);
					$args['name'] = '##like##'.$vlanName.'%';
				}

				$vlans = $this->_restAPI->cwVlans->search->get($args);
				$vlans = $this->_getCallResponse($vlans);

				// @todo temporaire le temps de mettre à jour les controleurs custom
				if(is_array($vlans))
				{
					foreach($vlans as &$vlan) {
						$vlan['id'] = $vlan['vlanId'];
						unset($vlan['vlanId']);
					}
				}

				return $vlans;
			}
			else {
				return false;
			}
		}

		public function searchAddresses($ip)
		{
			if(Tools::isIP($ip)) {
				$addresses = $this->_restAPI->addresses->search->{$ip}->get();
				return $this->_getCallResponse($addresses);
			}
			else {
				return false;
			}
		}

		// $strict for future use
		public function searchAddressIP($addressIP, $subnetId = null, $strict = false)
		{
			// Allow * or IP address
			if(C\Tools::is('string&&!empty', $addressIP))
			{
				$args = array();

				if(Tools::isIP($addressIP)) {
					$args['ip'] = $addressIP;
				}

				if(C\Tools::is('int&&>0', $subnetId)) {
					$args['subnetId'] = $subnetId;
				}

				$addresses = $this->_restAPI->cwAddresses->search->get($args);
				return $this->_getCallResponse($addresses);
			}
			else {
				return false;
			}
		}

		public function searchAddHostname($addHostname, $IPv = null, $subnetId = null, $strict = false)
		{
			if(C\Tools::is('string&&!empty', $addHostname))
			{
				$args = array();

				if($strict) {
					$args['hostname'] = $addHostname;
				}
				else {
					$addHostname = rtrim($addHostname, '*%');
					$addHostname = str_replace('*', '%', $addHostname);
					$args['hostname'] = '##like##'.$addHostname.'%';
				}

				if($IPv === 4 || $IPv === 6) {
					$args['ip_version'] = $IPv;
				}

				if(C\Tools::is('int&&>0', $subnetId)) {
					$args['subnetId'] = $subnetId;
				}

				$addresses = $this->_restAPI->cwAddresses->search->get($args);
				return $this->_getCallResponse($addresses);
			}
			else {
				return false;
			}
		}

		public function searchAddDescription($addDescription, $IPv = null, $subnetId = null, $strict = false)
		{
			if(C\Tools::is('string&&!empty', $addDescription))
			{
				$args = array();

				if($strict) {
					$args['description'] = $addDescription;
				}
				else {
					$addDescription = rtrim($addDescription, '*%');
					$addDescription = str_replace('*', '%', $addDescription);
					$args['description'] = '##like##'.$addDescription.'%';
				}

				if($IPv === 4 || $IPv === 6) {
					$args['ip_version'] = $IPv;
				}

				if(C\Tools::is('int&&>0', $subnetId)) {
					$args['subnetId'] = $subnetId;
				}

				$addresses = $this->_restAPI->cwAddresses->search->get($args);
				return $this->_getCallResponse($addresses);
			}
			else {
				return false;
			}
		}

		public function createAddress($subnetId, $address, $hostname, $description = '', $note = '', $port = '', $tag = self::ADDRESS_TAGS['online'])
		{
			if(!C\Tools::is('int&&>0', $subnetId) || !Tools::isIP($address)) {
				return false;
			}

			$args = array(
				'subnetId' => $subnetId,
				'ip' => $address,
				'hostname' => $hostname,
				'description' => $description,
				'note' => $note,
				'port' => $port,
			);

			if(array_key_exists($tag, self::ADDRESS_TAGS)) {
				$args['tag'] = self::ADDRESS_TAGS[$tag];
			}

			try {
				$result = $this->_restAPI->addresses->post($args);
			}
			catch(C\Exception $e)
			{
				$httpCode = $this->_restAPI->addresses->getHttpCode();

				switch($httpCode)
				{
					case 409: {
						throw new E\Message("Impossible de créer l'adresse IP, '".$address."' existe déjà", E_USER_ERROR);
					}
					default: {
						throw new E\Message("Impossible de créer l'adresse IP, HTTP code '".$httpCode."'", E_USER_ERROR);
					}
				}
			}

			$result = $this->_getCallResponse($result);
			return ($result !== false);
		}

		public function modifyAddress($addressId, $hostname = null, $description = null, $note = null, $port = null, $tag = null)
		{
			if(!C\Tools::is('int&&>0', $addressId)) {
				return false;
			}

			$args = array(
				'id' => $addressId,
				'hostname' => $hostname,
				'description' => $description,
				'note' => $note,
				'port' => $port,
			);

			$args = array_filter($args, function($item) {
				return ($item !== null);
			});

			if(array_key_exists($tag, self::ADDRESS_TAGS)) {
				$args['tag'] = self::ADDRESS_TAGS[$tag];
			}

			try {
				$result = $this->_restAPI->addresses->patch($args);
			}
			catch(C\Exception $e)
			{
				$httpCode = $this->_restAPI->addresses->getHttpCode();

				switch($httpCode)
				{
					default: {
						throw new E\Message("Impossible de modifier l'adresse IP, HTTP code '".$httpCode."'", E_USER_ERROR);
					}
				}
			}

			$result = $this->_getCallResponse($result);
			return ($result !== false);
		}

		public function removeAddress($addressId)
		{
			if(!C\Tools::is('int&&>0', $addressId)) {
				return false;
			}

			try {
				$result = $this->_restAPI->addresses->delete(array('id' => $addressId));
			}
			catch(C\Exception $e)
			{
				$httpCode = $this->_restAPI->addresses->getHttpCode();

				switch($httpCode)
				{
					default: {
						throw new E\Message("Impossible de supprimer l'adresse IP, HTTP code '".$httpCode."'", E_USER_ERROR);
					}
				}
			}

			$result = $this->_getCallResponse($result);
			return ($result !== false);
		}

		/**
		 * @param bool $debug
		 * @return $this
		 */
		public function debug($debug = true)
		{
			$this->_debug = (bool) $debug;

			foreach(self::REST_URN as $key => $urn)
			{
				if(isset($this->_restAPI->{$key})) {
					$this->_restAPI->{$key}->debug($this->_debug);
				}
			}

			return $this;
		}

		public function close()
		{
			foreach(self::REST_URN as $key => $urn)
			{
				if(isset($this->_restAPI->{$key})) {
					$this->_restAPI->{$key}->close();
					unset($this->_restAPI->{$key});
				}
			}
			return $this;
		}

		public function __destruct()
		{
			$this->close();
		}
	}