<?php
	namespace Addon\Ipam;

	use Core as C;

	class Api_Vlan extends Api_Abstract
	{
		const OBJECT_KEY = 'VLAN';
		const OBJECT_TYPE = 'vlan';
		const OBJECT_NAME = 'vlan';

		const FIELD_ID = 'id';
		const FIELD_NAME = 'name';
		const FIELD_DESC = 'description';

		/**
		  * Enable or disable cache feature
		  * /!\ Cache must be per type
		  *
		  * @var array
		  */
		protected static $_cache = array();		// IPAM server ID keys, boolean value

		/**
		  * All vlans (cache)
		  * /!\ Cache must be per type
		  *
		  * @var array
		  */
		protected static $_objects = array();	// IPAM server ID keys, array value

		/**
		  * @var array
		  */
		protected $_subnets = null;


		public function vlanIdIsValid($vlanId)
		{
			return $this->objectIdIsValid($vlanId);
		}

		public function hasVlanId()
		{
			return $this->hasObjectId();
		}

		public function getVlanId()
		{
			return $this->getObjectId();
		}

		public function vlanExists()
		{
			return $this->objectExists();
		}

		public function getVlanLabel()
		{
			return $this->getObjectLabel();
		}

		protected function _getObject()
		{
			if($this->_objectExists === null || $this->objectExists())
			{
				if($this->_objectDatas === null) {
					$this->_objectDatas = $this->_IPAM->getVlan($this->getVlanId());
				}

				return $this->_objectDatas;
			}
			else {
				return false;
			}
		}

		public function getSubnets()
		{
			if($this->_subnets === null || $this->objectExists())
			{
				if($this->_subnets === null) {
					$this->_subnets = $this->_IPAM->getSubnetsFromVlan($this->getVlanId());
				}

				return $this->_subnets;
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
			$this->_softReset($resetObjectId);
			$this->_resetAttributes();
		}

		protected function _resetAttributes()
		{
			$this->_subnets = null;
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'number': {
					return $this->_getField($name, 'string&&!empty');
				}
				case 'description': {
					return $this->_getField(self::FIELD_DESC, 'string&&!empty');
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
					case 'number': {
						return $this->_getField($name, 'string&&!empty');
					}
					case 'description': {
						return $this->_getField(self::FIELD_DESC, 'string&&!empty');
					}
				}
			}

			return parent::__call($method, $parameters);
		}

		/**
		  * Return all subnets matches request
		  *
		  * All arguments must be optional
		  *
		  * @param string $vlan VLAN number or name, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array
		  */
		public function getVlans($vlan = '*', $strict = false)
		{
			return $this->findVlans($vlan, $strict);
		}

		/**
		  * Return all subnets matches request
		  *
		  * @param string $vlan VLAN number or name, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array
		  */
		public function findVlans($vlan, $strict = false)
		{
			if($this->hasSubnetId()) {
				return self::_searchVlans($this->_IPAM, $vlan, $strict);
			}
			else {
				return false;
			}
		}

		/**
		  * Return all subnets matches request
		  *
		  * @param string $vlan VLAN number or name, wildcard * is allowed
		  * @param bool $strict
		  * @param Addon\Ipam\Main $IPAM IPAM connector
		  * @return false|array
		  */
		public static function searchVlans($vlan, $strict = false, Main $IPAM = null)
		{
			return self::_searchVlans($IPAM, $vlan, $strict);
		}

		/**
		  * Return all sections matches request
		  *
		  * @param Addon\Ipam\Main $IPAM IPAM connector
		  * @param string $vlan VLAN number or name, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array
		  */
		protected static function _searchVlans(Main $IPAM = null, $vlan = '*', $strict = false)
		{
			if($IPAM === null) {
				$IPAM = self::$_IPAM;
			}

			if(C\Tools::is('int&&>0', $vlan)) {
				return self::_searchVlanNumbers($IPAM, $vlan);
			}
			else {
				return self::_searchVlanNames($IPAM, $vlan, $strict);
			}
		}

		public function findVlanNumbers($vlanNumber)
		{
			return self::_searchVlanNumbers($this->_IPAM, $vlanNumber);
		}

		public static function searchVlanNumbers($vlanNumber)
		{
			return self::_searchVlanNumbers(null, $vlanNumber);
		}

		protected static function _searchVlanNumbers(Main $IPAM = null, $vlanNumber = null)
		{
			if($IPAM === null) {
				$IPAM = self::$_IPAM;
			}

			if(self::cacheEnabled($IPAM)) {
				$vlans = self::_getObjects($IPAM);
				return self::_filterObjects($vlans, 'number', (string) $vlanNumber);
			}
			else {
				return $IPAM->searchVlans($vlanNumber);
			}
		}

		public function findVlanNames($name, $strict = false)
		{
			return self::_searchVlanNames($this->_IPAM, $name, $strict);
		}

		public static function searchVlanNames($name, $strict = false)
		{
			return self::_searchVlanNames(null, $name, $strict);
		}

		protected static function _searchVlanNames(Main $IPAM = null, $name, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = self::$_IPAM;
			}

			if(self::cacheEnabled($IPAM)) {
				$vlans = self::_getObjects($IPAM);
				return self::_searchObjects($vlans, self::FIELD_NAME, $name, $strict);
			}
			else {
				return $IPAM->searchVlanName($name, $strict);
			}
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
			$result = $IPAM->getAllVlans();

			if($result !== false) {
				self::$_objects[$id] = $result;
				return true;
			}
			else {
				return false;
			}
		}
	}