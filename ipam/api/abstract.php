<?php
	abstract class Ipam_Api_Abstract
	{
		protected static $_IPAM = null;			// Global IPAM (enabled)
		protected static $_aIPAM = array();		// a = all/array/available IPAM

		protected $_IPAM_ = null;				// Local IPAM (for this instance)

		protected $_errorMessage = null;

		protected $_objectId = null;
		protected $_objectExists = null;		// /!\ Important null pour forcer la detection
		protected $_objectLabel = null;			// /!\ Important null pour forcer la detection

		protected $_objectDatas = null;


		public function __construct($objectId = null)
		{
			/**
			  * Permet de garder la référence de l'IPAM actuellement activé
			  * pour cette instance d'Ipam_Api_Abstract
			  */
			$this->_IPAM_ = self::$_IPAM;

			if($this->objectIdIsValid($objectId)) {
				$this->_objectId = (int) $objectId;
				$this->objectExists();
			}
			elseif($objectId !== null) {
				throw new Exception("This object ID '".$objectId."' is not valid", E_USER_ERROR);
			}
		}

		public static function getObjectType()
		{
			return static::OBJECT_TYPE;
		}

		public static function objectIdIsValid($objectId)
		{
			return Tools::is('int&&>0', $objectId);
		}

		public function hasObjectId()
		{
			return ($this->_objectId !== null);
		}

		public function getObjectId()
		{
			return $this->_objectId;
		}

		public function objectExists()
		{
			if(!$this->hasObjectId()) {
				return false;
			}
			elseif($this->_objectExists === null) {
				$this->_objectExists = ($this->_getObject() !== false);
			}
			return $this->_objectExists;
		}

		public function getObjectLabel()
		{
			if(!$this->hasObjectId()) {		// /!\ Ne pas appeler equipmentExists sinon boucle infinie
				return false;
			}
			elseif($this->_objectLabel === null) {
				$objectDatas = $this->_getObject();
				$this->_objectLabel = ($objectDatas !== false) ? ($objectDatas[static::FIELD_NAME]) : (false);
			}
			return $this->_objectLabel;
		}

		abstract protected function _getObject();

		protected function _getField($field, $validator = null)
		{
			if($this->objectExists())
			{
				$object = $this->_getObject($this->getObjectId());

				if($object !== false && ($validator === null || Tools::is($validator, $object[$field]))) {
					return $object[$field];
				}
			}

			return false;
		}

		protected function _getObjectId($objects, $fieldName, $name)
		{
			if($objects !== false)
			{
				foreach($objects as $object)
				{
					if($object[$fieldName] === $name) {
						return $object['id'];
					}
				}
			}

			return false;
		}

		protected function _getSubObjects($objects, $fieldName, $name)
		{
			if($objects !== false)
			{
				if($name !== null)
				{
					$subObjects = array();

					foreach($objects as $object)
					{
						if($object[$fieldName] === $name) {
							$subObjects[] = $object;
						}
					}

					return $subObjects;
				}
				else {
					return $objects;
				}
			}
			else {
				return false;
			}
		}

		protected static function _searchObjects(array $objects, $fieldName, $name, $strict = false)
		{
			$results = array();
			$name = preg_quote($name, '#');
			$name = str_replace('\*', '.*', $name);
			$name = ($strict) ? ('^('.$name.')$') : ('('.$name.')');

			foreach($objects as $object)
			{
				if(preg_match('#'.$name.'#i', $object[$fieldName])) {
					$results[] = $object;
				}
			}

			return $results;
		}

		public function __get($name)
		{
			switch(mb_strtolower($name))
			{
				case 'name':
				case 'label': {
					return $this->getObjectLabel();
				}
				case 'ipam':
				case '_ipam':
				case '_ipam_': {
					return $this->_IPAM_;
				}
				default: {
					throw new Exception("This attribute '".$name."' does not exist", E_USER_ERROR);
				}
			}
		}

		public function __call($method, $parameters = null)
		{
			throw new Exception("Method '".$method."' does not exist", E_USER_ERROR);
		}

		public function getErrorMessage()
		{
			return $this->_errorMessage;
		}

		public static function setIpam($IPAM)
		{
			if($IPAM instanceof IPAM_Main) {
				self::$_IPAM = $IPAM;
				return true;
			}
			elseif(Tools::is('array&&count>0', $IPAM))
			{
				$check = true;

				foreach($IPAM as $_IPAM)
				{
					if(!($_IPAM instanceof IPAM_Main)) {
						$check = false;
						break;
					}
				}

				if($check) {
					self::$_IPAM = current($IPAM);
					self::$_aIPAM = $IPAM;
					return true;
				}
			}

			throw new Exception("Unable to set IPAM object(s), it is not IPAM_Main instance or an array of it", E_USER_ERROR);
		}

		public static function getIpam()
		{
			return (count(self::$_aIPAM) > 0) ? (self::$_aIPAM) : (self::$_IPAM);
		}

		public static function enableIpam($key)
		{
			if(array_key_exists($key, self::$_aIPAM)) {
				self::$_IPAM = self::$_aIPAM[$key];
				return true;
			}
			else {
				return false;
			}
		}

		public static function getIpamEnabled()
		{
			return self::$_IPAM->getServerId();
		}
	}