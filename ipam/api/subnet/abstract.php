<?php
	abstract class Ipam_Api_Subnet_Abstract extends Ipam_Api_Abstract
	{
		const USAGE_FIELDS = array(
				'used' => 'used', 'total' => 'maxhosts', 'free' => 'freehosts', 'free%' => 'freehosts_percent',
				'offline' => 'Offline_percent', 'used%' => 'Used_percent', 'reserved%' => 'Reserved_percent', 'dhcp%' => 'DHCP_percent'
		);

		protected $_sectionApi;
		protected $_parentSubnetApi;


		public function getSectionId()
		{
			return $this->_getField('sectionId', 'int&&>0');
		}

		public function getSection()
		{
			$sectionId = $this->getSectionId();

			if($sectionId !== false) {
				return $this->_IPAM->getSection($sectionId);
			}
			else {
				return false;
			}
		}

		public function getSectionApi()
		{
			if($this->_sectionApi === null)
			{
				$sectionId = $this->getSectionId();

				if($sectionId !== false) {
					$this->_sectionApi = new Ipam_Api_Section($sectionId);
				}
				else {
					$this->_sectionApi = false;
				}
			}

			return $this->_sectionApi;
		}

		public function getParentSubnetId()
		{
			return $this->_getField('masterSubnetId', 'int&&>0');
		}

		public function getParentSubnet()
		{
			$parentSubnetId = $this->getParentSubnetId();

			if($parentSubnetId !== false) {
				return $this->_IPAM->getSubnet($parentSubnetId);
			}
			else {
				return false;
			}
		}

		public function getParentSubnetApi()
		{
			if($this->_parentSubnetApi === null)
			{
				$parentSubnetId = $this->getParentSubnetId();

				if($parentSubnetId !== false) {
					$this->_parentSubnetApi = new Ipam_Api_Subnet($parentSubnetId);
				}
				else {
					$this->_parentSubnetApi = false;
				}
			}

			return $this->_parentSubnetApi;
		}

		public function getPath()
		{
			$path = $this->_getPath();
			array_pop($path);
			return $path;
		}

		/**
		  * @todo sujet au bug PHPIPAM
		  * curl -vvv -H "token: [token]" -H "Content-Type: application/json; charset=utf-8" https://ipam.corp.cloudwatt.com/api/myAPP/folders/1185/
		  * Fatal error</b>:  Unsupported operand types in <b>/opt/phpipam/functions/classes/class.Tools.php</b> on line <b>1695</b>
		  */
		protected function _getPath()
		{
			$subnetId = $this->_getField('masterSubnetId', 'int&&>0');
			$isFolder = (bool) $this->_getField('isFolder', 'binary');

			if($this->objectIdIsValid($subnetId))
			{
				if(!$isFolder) {
					$objectApi = new Ipam_Api_Subnet($subnetId);
				}
				else {
					$objectApi = new Ipam_Api_Folder($subnetId);
				}
			}
			else
			{
				$sectionId = $this->_getField('sectionId', 'int&&>0');

				if(Ipam_Api_Section::objectIdIsValid($sectionId)) {
					$objectApi = new Ipam_Api_Section($sectionId);
				}
				else {
					$subnetLabel = $this->getSubnetLabel();
					if($subnetLabel === false) { $subnetLabel = 'ROOT'; }	//@todo a supprimer une fois le bug Unsupported operand types corrigé
					return array($subnetLabel);
				}
			}

			$path = $objectApi->_getPath();
			$path[] = $this->getSubnetLabel();
			return $path;
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'sectionApi': {
					return $this->getSectionApi();
				}
				case 'subnetApi':
				case 'parentSubnetApi': {
					return $this->getParentSubnetApi();
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