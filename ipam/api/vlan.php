<?php
	class Ipam_Api_Vlan extends Ipam_Api_Abstract
	{
		const OBJECT_TYPE = 'vlan';
		const FIELD_NAME = 'name';

		protected static $_vlans = array();

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

		public function getNumber()
		{
			return $this->_getField('number', 'int&&>0');
		}

		public function getName()
		{
			return $this->_getField('name', 'string&&!empty');
		}

		public function getDescription()
		{
			return $this->_getField('description', 'string&&!empty');
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

		public function findVlanNumbers($vlanNumber)
		{
			return self::_searchVlanNumbers($vlanNumber, $this->_IPAM);
		}

		public static function searchVlanNumbers($vlanNumber)
		{
			return self::_searchVlanNumbers($vlanNumber);
		}

		protected static function _searchVlanNumbers($vlanNumber, IPAM_Main $IPAM = null)
		{
			if($IPAM === null) {
				$IPAM = self::$_IPAM;
			}

			return $IPAM->searchVlans($vlanNumber);
		}

		public function findVlanNames($vlanName, $strict = false)
		{
			$vlans = self::_getVlans($this->_IPAM);
			return self::_searchObjects($vlans, self::FIELD_NAME, $vlanName, $strict);
		}

		public static function searchVlanNames($vlanName, $strict = false)
		{
			$vlans = self::_getVlans();
			return self::_searchObjects($vlans, self::FIELD_NAME, $vlanName, $strict);
		}

		protected static function _getVlans(IPAM_Main $IPAM = null)
		{
			if($IPAM === null) {
				$IPAM = self::$_IPAM;
			}

			$id = $IPAM->getServerId();

			if(!array_key_exists($id, self::$_vlans)) {
				self::$_vlans[$id] = $IPAM->getAllVlans();
			}

			return self::$_vlans[$id];
		}
	}