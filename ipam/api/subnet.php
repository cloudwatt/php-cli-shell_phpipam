<?php
	class Ipam_Api_Subnet extends Ipam_Api_Subnet_Abstract
	{
		const OBJECT_TYPE = 'subnet';
		const FIELD_NAME = 'description';

		const USAGE_FIELDS = array(
				'used' => 'used', 'total' => 'maxhosts', 'free' => 'freehosts', 'free%' => 'freehosts_percent',
				'offline' => 'Offline_percent', 'used%' => 'Used_percent', 'reserved%' => 'Reserved_percent', 'dhcp%' => 'DHCP_percent'
		);

		protected static $_subnets = array();

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

		protected function _getObject()
		{
			if($this->_objectExists === null || $this->objectExists())
			{
				if($this->_objectDatas === null) {
					$this->_objectDatas = $this->_IPAM->getSubnet($this->getSubnetId());
				}

				return $this->_objectDatas;
			}
			else {
				return false;
			}
		}

		public function getSubSubnets($subnetName = null)
		{
			if($this->subnetExists()) {
				$subnets = $this->_IPAM->getSubnets($this->getSectionId(), $this->getSubnetId());
				return $this->_getSubObjects($subnets, self::FIELD_NAME, $subnetName);
			}

			return false;
		}

		public function getSubSubnetId($subnetName)
		{
			$subnets = $this->getSubSubnets($subnetName);
			return ($subnets !== false && count($subnets) === 1) ? ($subnets[0]['id']) : (false);
		}

		public function getVlanId()
		{
			return $this->_getField('vlanId', 'int&&>0');
		}

		public function getVlan()
		{
			$vlanId = $this->getVlanId();

			if($vlanId !== false) {
				return $this->_IPAM->getVlan($vlanId);
			}
			else {
				return false;
			}
		}

		public function getVlanApi()
		{
			if($this->_vlanApi === null)
			{
				$vlanId = $this->getVlanId();

				if($vlanId !== false) {
					$this->_vlanApi = new Ipam_Api_Vlan($vlanId);
				}
				else {
					$this->_vlanApi = false;
				}
			}

			return $this->_vlanApi;
		}

		public function getSubnet()
		{
			return $this->getNetwork().'/'.$this->getCidrMask();
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
					return IPAM_Tools::cidrMaskToNetMask($cidrMask);
				}
				elseif($this->isIPv6()) {
					return $cidrMask;
				}
			}

			return false;
		}

		public function getFirstIp()
		{
			return IPAM_Tools::firstSubnetIp($this->getNetwork(), $this->getNetMask());
		}

		public function getLastIp()
		{
			return IPAM_Tools::lastSubnetIp($this->getNetwork(), $this->getNetMask());
		}

		public function getNetworkIp()
		{
			return IPAM_Tools::networkIp($this->getNetwork(), $this->getNetMask());
		}

		public function getBroadcastIp()
		{
			return IPAM_Tools::broadcastIp($this->getNetwork(), $this->getNetMask());
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

		public function getAddresses()
		{
			if($this->subnetExists()) {
				return $this->_IPAM->getAddresses($this->getSubnetId());
			}
			else {
				return false;
			}
		}

		public function getAddress($ip)
		{
			$address = $this->_IPAM->getAddress($this->getSubnetId(), $ip);
			return ($address !== false) ? ($address) : (false);
		}

		public function getAddressId($ip)
		{
			$address = $this->getAddress($ip);
			return ($address !== false) ? ($address['id']) : (false);
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'subnet': {
					return $this->getSubnet();
				}
				case 'vlanApi': {
					return $this->getVlanApi();
				}
				default: {
					return parent::__get($name);
				}
			}
		}

		public function __call($method, $parameters = null)
		{
			switch($method)
			{
				case 'isIPv4':
				case 'isIPv6':
					$subnet = $this->_getField('subnet');
					return forward_static_call(array(static::class, $method.'Subnet'), $subnet);
				default:
					return parent::__call($method, $parameters);
			}
		}

		public function findCidrSubnets($cidrSubnet, $strict = false)
		{
			return self::_searchCidrSubnets($cidrSubnet, $this->_IPAM);
		}

		public static function searchCidrSubnets($cidrSubnet, $strict = false)
		{
			return self::_searchCidrSubnets($cidrSubnet, null, $strict);
		}

		// $strict for future use
		protected static function _searchCidrSubnets($cidrSubnet, IPAM_Main $IPAM = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = self::$_IPAM;
			}

			if(self::isIPv4Subnet($cidrSubnet) && strpos($cidrSubnet, '/') !== false)
			{
				$cidrSubnet = IPAM_Tools::networkSubnet($cidrSubnet);

				if($cidrSubnet === false) {
					return false;
				}
			}

			return $IPAM->searchSubnets($cidrSubnet);
		}

		public function findSubnetNames($subnetName, $strict = false)
		{
			$subnets = self::_getSubnets($this->_IPAM);
			return self::_searchObjects($subnets, self::FIELD_NAME, $subnetName, $strict);
		}

		public static function searchSubnetNames($subnetName, $strict = false)
		{
			$subnets = self::_getSubnets();
			return self::_searchObjects($subnets, self::FIELD_NAME, $subnetName, $strict);
		}

		protected static function _getSubnets(IPAM_Main $IPAM = null)
		{
			if($IPAM === null) {
				$IPAM = self::$_IPAM;
			}

			$id = $IPAM->getServerId();

			if(!array_key_exists($id, self::$_subnets)) {
				self::$_subnets[$id] = $IPAM->getAllSubnets();
			}

			return self::$_subnets[$id];
		}

		public static function isIPv4Subnet($subnet)
		{
			if(Tools::is('array&&count>0', $subnet)) {
				$subnet = $subnet['subnet'];
			}

			if($subnet !== false) {
				// Be careful ::ffff:127.0.0.1 notation is valid
				//return (substr_count($subnet, '.') === 3 && strpos($subnet, ':') === false);
				return Tools::is('ipv4', $subnet);
			}
			else {
				return false;
			}
		}

		public static function isIPv6Subnet($subnet)
		{
			if(Tools::is('array&&count>0', $subnet)) {
				$subnet = $subnet['subnet'];
			}

			if($subnet !== false) {
				//return (strpos($subnet, ':') !== false);
				return Tools::is('ipv6', $subnet);
			}
			else {
				return false;
			}
		}
	}