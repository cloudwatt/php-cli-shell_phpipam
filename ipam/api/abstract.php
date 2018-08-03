<?php
	abstract class Ipam_Api_Abstract
	{
		protected static $_IPAM = null;

		protected $_errorMessage = null;

		protected $_objectId = null;
		protected $_objectExists = null;		// /!\ Important null pour forcer la detection
		protected $_objectLabel = null;			// /!\ Important null pour forcer la detection

		protected $_objectDatas = null;


		public function __construct($objectId = null)
		{
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

		static protected function _searchObjects(array $objects, $fieldName, $name)
		{
			$results = array();
			$name = preg_quote($name, '#');
			$name = str_replace('\*', '.*', $name);

			foreach($objects as $object)
			{
				if(preg_match('#('.$name.')#i', $object[$fieldName])) {
					$results[] = $object;
				}
			}

			return $results;
		}

		public function __get($name)
		{
			switch(mb_strtolower($name))
			{
				case 'ipam':
				case '_ipam': {
					return self::$_IPAM;
				}
				default: {
					throw new Exception("This attribute '".$name."' does not exist", E_USER_ERROR);
				}
			}
		}

		public function __call($method, $parameters = null)
		{
			throw new Exception('Method '.$method.' does not exist', E_USER_ERROR);
		}

		public function getErrorMessage()
		{
			return $this->_errorMessage;
		}

		public static function setIpam(IPAM_Main $IPAM)
		{
			self::$_IPAM = $IPAM;
		}
	}