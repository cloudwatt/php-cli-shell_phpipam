<?php
	namespace Addon\Ipam;

	use Core as C;

	class Api_Subnet extends Api_Subnet_Abstract
	{
		const OBJECT_KEY = 'SUBNET';
		const OBJECT_TYPE = 'subnet';
		const OBJECT_NAME = 'subnet';

		const FIELD_ID = 'id';
		const FIELD_NAME = 'description';

		const USAGE_FIELDS = array(
				'used' => 'used', 'total' => 'maxhosts', 'free' => 'freehosts', 'free%' => 'freehosts_percent',
				'offline' => 'Offline_percent', 'used%' => 'Used_percent', 'reserved%' => 'Reserved_percent', 'dhcp%' => 'DHCP_percent'
		);

		/**
		  * Enable or disable cache feature
		  * /!\ Cache must be per type
		  *
		  * @var array
		  */
		protected static $_cache = array();		// IPAM server ID keys, boolean value

		/**
		  * All subnets (cache)
		  * /!\ Cache must be per type
		  *
		  * @var array
		  */
		protected static $_objects = array();	// IPAM server ID keys, array value

		/**
		  * @var Addon\Ipam\Api_Vlan
		  */
		protected $_vlanApi = null;


		public function subnetIdIsValid($subnetId)
		{
			return $this->objectIdIsValid($subnetId);
		}

		public function hasSubnetId()
		{
			return $this->hasObjectId();
		}

		public function getSubnetId()
		{
			return $this->getObjectId();
		}

		public function subnetExists()
		{
			return $this->objectExists();
		}

		public function getSubnetLabel()
		{
			return $this->getObjectLabel();
		}

		protected function _formatName(array $subnet)
		{
			if(!C\Tools::is('string&&!empty', $subnet[self::FIELD_NAME])) {
				$subnet[self::FIELD_NAME] = $subnet['subnet'].'/'.$subnet['mask'];
			}

			return $subnet;
		}

		protected function _getObject()
		{
			if($this->_objectExists === null || $this->objectExists())
			{
				if($this->_objectDatas === null) {
					$objectDatas = $this->_IPAM->getSubnet($this->getSubnetId());
					$objectDatas = $this->_formatName($objectDatas);
					$this->_objectDatas = $objectDatas;
				}

				return $this->_objectDatas;
			}
			else {
				return false;
			}
		}

		protected function _setObject(array $datas)
		{
			if(static::objectIdIsValid($datas[static::FIELD_ID]))
			{
				$datas = $this->_formatName($datas);

				$this->_objectId = $datas[static::FIELD_ID];
				$this->_objectLabel = $datas[static::FIELD_NAME];
				$this->_objectDatas = $datas;
				$this->_objectExists = true;
				return true;
			}
			else {
				return false;
			}
		}

		public function isIPv($IPv)
		{
			switch($IPv)
			{
				case 4: {
					return $this->isIPv4();
				}
				case 6: {
					return $this->isIPv6();
				}
				default: {
					return false;
				}
			}
		}

		public function isIPv4()
		{
			$subnet = $this->getCidrSubnet();
			return Tools::isSubnetV4($subnet);
		}

		public function isIPv6()
		{
			$subnet = $this->getCidrSubnet();
			return Tools::isSubnetV6($subnet);
		}

		public function getCidrSubnet()
		{
			return $this->getNetwork().'/'.$this->getCidrMask();
		}

		public function getNetSubnet()
		{
			return $this->getNetwork().'/'.$this->getNetMask();
		}

		public function getNetwork()
		{
			return $this->_getField('subnet', 'string&&!empty');
		}

		public function getCidrMask()
		{
			return $this->_getField('mask', 'int&&>0');
		}

		public function getNetMask()
		{
			$cidrMask = $this->getCidrMask();

			if($cidrMask !== false)
			{
				if($this->isIPv4()) {
					return Tools::cidrMaskToNetMask($cidrMask);
				}
				elseif($this->isIPv6()) {
					return $cidrMask;
				}
			}

			return false;
		}

		public function getFirstIp()
		{
			return Tools::firstSubnetIp($this->getNetwork(), $this->getNetMask());
		}

		public function getLastIp()
		{
			return Tools::lastSubnetIp($this->getNetwork(), $this->getNetMask());
		}

		public function getNetworkIp()
		{
			return Tools::networkIp($this->getNetwork(), $this->getNetMask());
		}

		public function getBroadcastIp()
		{
			return Tools::broadcastIp($this->getNetwork(), $this->getNetMask());
		}

		public function getGateway()
		{
			$addresses = $this->getAddresses();

			if($addresses !== false)
			{
				foreach($addresses as $address)
				{
					if($address['is_gateway'] === 1) {
						return $address['ip'];
					}
				}
			}

			return false;
		}

		public function getUsage()
		{
			if($this->subnetExists()) {
				return $this->_IPAM->getSubnetUsage($this->getSubnetId());
			}
			else {
				return false;
			}
		}

		public function getSubSubnet($subnetName)
		{
			$subnets = $this->getSubSubnets($subnetName);
			return ($subnets !== false && count($subnets) === 1) ? ($subnets[0]) : (false);
		}

		public function getSubSubnetId($subnetName)
		{
			$subnet = $this->getSubSubnet($subnetName);
			return ($subnet !== false) ? ($subnet[self::FIELD_ID]) : (false);
		}

		public function getSubSubnetApi($subnetName)
		{
			$subnetId = $this->getSubSubnetId($subnetName);
			return ($subnetId !== false) ? (new self($subnetId)) : (false);
		}

		public function getSubSubnets($subnetName = null)
		{
			if($this->subnetExists()) {
				return self::searchSubnets($subnetName, null, $this->getSectionId(), null, null, true, $this->_IPAM);
			}
			else {
				return false;
			}
		}

		// @todo cache
		/*public function getVlan()
		{
			$vlanId = $this->getVlanId();

			if($vlanId !== false) {
				return $this->_IPAM->getVlan($vlanId);
			}
			else {
				return false;
			}
		}*/

		public function getVlanId()
		{
			return $this->_getField('vlanId', 'int&&>0', 'int');
		}

		public function getVlanApi()
		{
			if($this->_vlanApi === null)
			{
				$vlanId = $this->getVlanId();

				if($vlanId !== false) {
					$this->_vlanApi = new Api_Vlan($vlanId);
				}
				else {
					$this->_vlanApi = false;
				}
			}

			return $this->_vlanApi;
		}

		/**
		  * @param string IP address or name
		  * @return false|array
		  */
		public function getAddress($address)
		{
			if(Tools::isIP($address)) {
				$address = $this->_IPAM->getAddress($this->getSubnetId(), $address);
				return ($address !== false) ? ($address) : (false);
			}
			else {
				$addresses = $this->getAddresses($address);
				return ($addresses !== false && count($addresses) === 1) ? ($addresses[0]) : (false);
			}
		}

		/**
		  * @param string IP address or name
		  * @return false|int
		  */
		public function getAddressId($address)
		{
			$address = $this->getAddress($address);
			return ($address !== false) ? ($address[Api_Address::FIELD_ID]) : (false);
		}

		/**
		  * @param string IP address or name
		  * @return false|Addon\Ipam\Api_Address
		  */
		public function getAddressApi($address)
		{
			$addressId = $this->getAddressId($address);
			return ($addressId !== false) ? (new Api_Address($addressId)) : (false);
		}

		/**
		  * @param null|string Address name
		  * @return false|array
		  */
		public function getAddresses($addressName = null)
		{
			if($this->subnetExists()) {
				$addresses = $this->_IPAM->getAddresses($this->getSubnetId());
				return $this->_filterObjects($addresses, self::FIELD_NAME, $addressName);
			}
			else {
				return false;
			}
		}

		/**
		  * Return all addresses matches request
		  *
		  * @param string $address IP address or name, wildcard * is allowed for name only
		  * @param int $IPv IP version, 4 or 6
		  * @param bool $strict
		  * @return false|array
		  */
		public function findAddresses($address, $IPv = null, $strict = false)
		{
			if($this->subnetExists())
			{
				if(Tools::isIP($address)) {
					return Api_Address::searchIpAddresses($address, $this->getSubnetId(), $strict);
				}
				else {
					return Api_Address::searchAddresses($address, $IPv, $this->getSubnetId(), $strict);
				}
			}
			else {
				return false;
			}
		}

		/**
		  * @param bool $resetObjectId
		  * @return void
		  */
		protected function _hardReset($resetObjectId = false)
		{
			parent::_hardReset($resetObjectId);
			$this->_resetVlan();
		}

		protected function _resetVlan()
		{
			$this->_vlanApi = null;
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'cidr':
				case 'subnet':
				case 'cidrSubnet': {
					return $this->getCidrSubnet();
				}
				case 'vlanApi': {
					return $this->getVlanApi();
				}
				default: {
					return parent::__get($name);
				}
			}
		}

		/**
		  * Return all subnets matches request
		  *
		  * All arguments must be optional
		  *
		  * @param string $subnet Subnet CIDR or name, wildcard * is allowed
		  * @param int $IPv IP version, 4 or 6
		  * @param bool $strict
		  * @return false|array
		  */
		public function getSubnets($subnet = '*', $IPv = null, $strict = false)
		{
			return $this->findSubnets($subnet, $IPv, $strict);
		}

		/**
		  * Return all subnets matches request
		  *
		  * @param string $subnet Subnet CIDR or name, wildcard * is allowed
		  * @param int $IPv IP version, 4 or 6
		  * @param bool $strict
		  * @return false|array
		  */
		public function findSubnets($subnet, $IPv = null, $strict = false)
		{
			if($this->hasSubnetId()) {
				return self::_searchSubnets($this->_IPAM, $subnet, $IPv, $this->getSubnetId(), null, null, $strict);
			}
			else {
				return false;
			}
		}

		/**
		  * Return all subnets matches request
		  *
		  * @param string $subnet Subnet CIDR or name, wildcard * is allowed
		  * @param int $IPv IP version, 4 or 6
		  * @param int $subnetId Subnet ID
		  * @param int $folderId Folder ID
		  * @param int $sectionId Section ID
		  * @param bool $strict
		  * @param Addon\Ipam\Main $IPAM IPAM connector
		  * @return false|array
		  */
		public static function searchSubnets($subnet, $IPv = null, $subnetId = null, $folderId = null, $sectionId = null, $strict = false, Main $IPAM = null)
		{
			return self::_searchSubnets($IPAM, $subnet, $IPv, $subnetId, $folderId, $sectionId, $strict);
		}

		/**
		  * Return all sections matches request
		  *
		  * @param Addon\Ipam\Main $IPAM IPAM connector
		  * @param string $subnet Subnet CIDR or name, wildcard * is allowed
		  * @param int $IPv IP version, 4 or 6
		  * @param int $subnetId Subnet ID
		  * @param int $folderId Folder ID
		  * @param int $sectionId Section ID
		  * @param bool $strict
		  * @return false|array
		  */
		protected static function _searchSubnets(Main $IPAM = null, $subnet = '*', $IPv = null, $subnetId = null, $folderId = null, $sectionId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = self::$_IPAM;
			}

			if(Tools::isSubnet($subnet)) {
				return self::_searchCidrSubnets($IPAM, $subnet, $subnetId, $folderId, $sectionId, $strict);
			}
			else {
				return self::_searchSubnetNames($IPAM, $subnet, $IPv, $subnetId, $folderId, $sectionId, $strict);
			}
		}

		public function findCidrSubnets($subnet, $subnetId = null, $folderId = null, $sectionId = null, $strict = false)
		{
			return self::_searchCidrSubnets($this->_IPAM, $subnet, $subnetId, $folderId, $sectionId, $strict);
		}

		public static function searchCidrSubnets($subnet, $subnetId = null, $folderId = null, $sectionId = null, $strict = false)
		{
			return self::_searchCidrSubnets(null, $subnet, $subnetId, $folderId, $sectionId, $strict);
		}

		// $strict for future use
		protected static function _searchCidrSubnets(Main $IPAM = null, $subnet = null, $subnetId = null, $folderId = null, $sectionId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = self::$_IPAM;
			}

			if($subnet !== null)
			{
				if(Tools::isSubnet($subnet))
				{
					$subnet = Tools::networkSubnet($subnet);

					if($subnet === false) {
						return false;
					}

					if(self::cacheEnabled($IPAM))
					{
						$subnets = self::_getObjects($IPAM);

						if(C\Tools::is('int&&>=0', $subnetId)) {
							$subnets = self::_filterObjects($subnets, 'masterSubnetId', (string) $subnetId);
						}
						elseif(C\Tools::is('int&&>=0', $folderId)) {
							$subnets = self::_filterObjects($subnets, 'masterSubnetId', (string) $folderId);
						}
						// Pas de elseif
						if(C\Tools::is('int&&>=0', $sectionId)) {
							$subnets = self::_filterObjects($subnets, 'sectionId', (string) $sectionId);
						}

						foreach($subnets as $index => $_subnet)
						{
							$_subnet = $_subnet['subnet'].'/'.$_subnet['mask'];

							if(!Tools::subnetInSubnet($_subnet, $subnet)) {
								unset($subnets[$index]);
							}
						}

						return $subnets;
					}
					else
					{
						if(!C\Tools::is('int&&>=0', $subnetId)) {
							$subnetId = $folderId;
						}

						return $IPAM->searchSubnetCidr($subnet, $subnetId, $sectionId, $strict);
					}
				}
			}
			else {
				// @todo return all subnets?
				//return self::_searchSubnetNames($IPAM, '*', null, $subnetId, $folderId, $sectionId, $strict);
			}

			return array();
		}

		public function findSubnetNames($name, $IPv = null, $subnetId = null, $folderId = null, $sectionId = null, $strict = false)
		{
			return self::_searchSubnetNames($this->_IPAM, $name, $IPv, $subnetId, $folderId, $sectionId, $strict);
		}

		public static function searchSubnetNames($name, $IPv = null, $subnetId = null, $folderId = null, $sectionId = null, $strict = false)
		{
			return self::_searchSubnetNames(null, $name, $IPv, $subnetId, $folderId, $sectionId, $strict);
		}

		protected static function _searchSubnetNames(Main $IPAM = null, $name = '*', $IPv = null, $subnetId = null, $folderId = null, $sectionId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = self::$_IPAM;
			}

			$subnets = array();

			if($name === null) {
				$name = '*';
			}

			if($sectionId === null && $folderId === null && $subnetId === null)
			{
				$separator = preg_quote(self::SEPARATOR_SECTION, '#');
				$status = preg_match('#(?:'.$separator.'(?<section>.+?)'.$separator.')?(?<name>.+)#i', $name, $nameParts);

				if($status && C\Tools::is('string&&!empty', $nameParts['section']) && C\Tools::is('string&&!empty', $nameParts['name']))
				{
					$sections = Api_Section::searchSections($nameParts['section'], null, true);

					if($sections !== false && count($sections) === 1) {
						$name = $nameParts['name'];
						$sectionId = $sections[0][Api_Section::FIELD_ID];
						$sectionName = $sections[0][Api_Section::FIELD_NAME];
					}
					else {
						return $subnets;
					}
				}
			}

			if(self::cacheEnabled($IPAM))
			{
				$subnets = self::_getObjects($IPAM);

				if(C\Tools::is('int&&>=0', $subnetId)) {
					$subnets = self::_filterObjects($subnets, 'masterSubnetId', (string) $subnetId);
				}
				elseif(C\Tools::is('int&&>=0', $folderId)) {
					$subnets = self::_filterObjects($subnets, 'masterSubnetId', (string) $folderId);
				}
				// Pas de elseif
				if(C\Tools::is('int&&>=0', $sectionId)) {
					$subnets = self::_filterObjects($subnets, 'sectionId', (string) $sectionId);
				}

				if($IPv === 4 || $IPv === 6)
				{
					foreach($subnets as $index => $subnet)
					{
						$subnetCidr = $subnet['subnet'].'/'.$subnet['mask'];

						if(!Tools::isSubnetV($subnetCidr, $IPv)) {
							unset($subnets[$index]);
						}
					}
				}

				$subnets = self::_searchObjects($subnets, self::FIELD_NAME, $name, $strict);
			}
			else
			{
				if(!C\Tools::is('int&&>=0', $subnetId)) {
					$subnetId = $folderId;
				}

				$subnets = $IPAM->searchSubnetName($name, $IPv, $subnetId, $sectionId, $strict);
			}

			if(isset($sectionName))
			{
				foreach($subnets as &$subnet) {
					$subnetNamePrefix = self::SEPARATOR_SECTION.$sectionName.self::SEPARATOR_SECTION;
					$subnet[self::FIELD_NAME] = $subnetNamePrefix.$subnet[self::FIELD_NAME];
				}
				unset($subnet);
			}

			return $subnets;
		}

		/**
		  * @param Addon\Ipam\Main $IPAM
		  * @return bool
		  */
		protected static function _setObjects(C\Addon\Adapter $IPAM = null)
		{
			if($IPAM === null) {
				$IPAM = self::$_IPAM;
			}

			$id = $IPAM->getServerId();
			$result = $IPAM->getAllSubnets();

			if($result !== false) {
				self::$_objects[$id] = $result;
				return true;
			}
			else {
				return false;
			}
		}
	}