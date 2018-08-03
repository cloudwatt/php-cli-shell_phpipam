<?php
	class Ipam_Api_Address extends Ipam_Api_Abstract
	{
		const OBJECT_TYPE = 'address';
		const FIELD_NAME = 'hostname';

		const TAGS = array(
			1 => 'offline',
			2 => 'online',
			3 => 'reserved',
			4 => 'DHCP',
		);

		protected $_subnetApi;


		public function addressIdIsValid($addressId)
		{
			return $this->objectIdIsValid($addressId);
		}

		public function hasAddressId()
		{
			return $this->hasObjectId();
		}

		public function getAddressId()
		{
			return $this->getObjectId();
		}

		public function addressExists()
		{
			return $this->objectExists();
		}

		public function getAddressLabel()
		{
			return $this->getObjectLabel();
		}

		protected function _getObject()
		{
			if($this->_objectExists === null || $this->objectExists())
			{
				if($this->_objectDatas === null) {
					$this->_objectDatas = $this->_IPAM->getAddress($this->getAddressId());
				}

				return $this->_objectDatas;
			}
			else {
				return false;
			}
		}

		public function getSubnetId()
		{
			return $this->_getField('subnetId', 'int&&>0');
		}

		public function getSubnet()
		{
			$subnetId = $this->getSubnetId();

			if($subnetId !== false) {
				return $this->_IPAM->getSubnet($subnetId);
			}
			else {
				return false;
			}
		}

		public function getSubnetApi()
		{
			if($this->_subnetApi === null)
			{
				$subnetId = $this->getSubnetId();

				if($subnetId !== false) {
					$this->_subnetApi = new Ipam_Api_Subnet($subnetId);
				}
				else {
					$this->_subnetApi = false;
				}
			}

			return $this->_subnetApi;
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'ip':
				case 'hostname':
				case 'description':
				case 'tag':
				case 'note': {
					return $this->_getField($name, 'string&&!empty');
				}
				case 'subnetApi': {
					return $this->getSubnetApi();
				}
				case 'vlanApi': {
					$subnetApi = $this->getSubnetApi();
					return ($subnetApi !== false) ? ($subnetApi->vlanApi) : (false);
				}
				default: {
					return parent::__get($name);
				}
			}
		}

		public function __call($method, $parameters = null)
		{
			if(substr($method, 0, 3) === 'get')
			{
				$name = substr($method, 3);
				$name = mb_strtolower($name);

				switch($name)
				{
					case 'ip':
					case 'hostname':
					case 'description':
					case 'tag':
					case 'note':
						return $this->_getField($name, 'string&&!empty');
				}
			}

			return parent::__call($method, $parameters);
		}

		public static function searchIpAddresses($ip)
		{
			return self::$_IPAM->searchAddresses($ip);
		}

		public static function searchAddressNames($addressName)
		{
			return self::$_IPAM->searchAddHostname($addressName);
		}

		public static function searchAddressDescs($addressDesc)
		{
			return self::$_IPAM->searchAddDescription($addressDesc);
		}
	}