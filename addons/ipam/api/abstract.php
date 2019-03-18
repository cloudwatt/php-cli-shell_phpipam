<?php
	namespace Addon\Ipam;

	use Core as C;

	abstract class Api_Abstract extends C\Addon\Api_Abstract
	{
		const FIELD_ID = 'id';
		const FIELD_NAME = 'name';

		const WILDCARD = '*';
		const SEPARATOR_PATH = ',';
		const SEPARATOR_SECTION = '#';

		/**
		  * @var string
		  */
		protected static $_parentAdapter = __NAMESPACE__ .'\Main';

		/**
		  * @var Addon\Ipam\Main
		  */
		protected static $_IPAM = null;			// Global IPAM (enabled)

		/**
		  * @var Addon\Ipam\Main[]
		  */
		protected static $_aIPAM = array();		// a = all/array/available IPAM

		/**
		  * @var Addon\Ipam\Main
		  */
		protected $_IPAM_ = null;				// Local IPAM (for this instance)


		public function __construct($objectId = null)
		{
			parent::__construct($objectId);
			$this->_IPAM_ = &$this->_ownerAdapter;	// /!\ A executer avant _setObjectId
			$this->_setObjectId($objectId);			// @todo temp
		}

		public static function objectIdIsValid($objectId)
		{
			return C\Tools::is('int&&>0', $objectId);
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

		protected function _setObjectLabel($objectLabel)
		{
			if(!$this->objectExists() && C\Tools::is('string&&!empty', $objectLabel)) {
				$this->_objectLabel = $objectLabel;
				return true;
			}
			else {
				return false;
			}
		}

		public function hasObjectLabel()
		{
			return ($this->getObjectLabel() !== false);
		}

		public function getObjectLabel()
		{
			if($this->_objectLabel !== null) {		// /!\ Ne pas appeler hasObjectLabel sinon boucle infinie
				return $this->_objectLabel;
			}
			elseif($this->hasObjectId()) {			// /!\ Ne pas appeler objectExists sinon boucle infinie
				$objectDatas = $this->_getObject();
				$this->_objectLabel = ($objectDatas !== false) ? ($objectDatas[static::FIELD_NAME]) : (false);
				return $this->_objectLabel;
			}
			else {
				return false;
			}
		}

		/**
		  * Méthode courte comme getPath
		  */
		public function getLabel()
		{
			return $this->getObjectLabel();
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'ipam':
				case '_IPAM': {
					return $this->_IPAM_;
				}
				case 'id': {
					return $this->getObjectId();
				}
				case 'name':
				case 'label': {
					return $this->getObjectLabel();
				}
				default: {
					throw new Exception("This attribute '".$name."' does not exist", E_USER_ERROR);
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
					case 'name': {
						return $this->_getField(static::FIELD_NAME, 'string&&!empty');
					}
				}
			}

			throw new Exception("Method '".$method."' does not exist", E_USER_ERROR);
		}

		/**
		  * @param Addon\Ipam\Main|Addon\Ipam\Main[] $IPAM
		  * @return bool
		  */
		public static function setIpam($IPAM)
		{
			return self::setAdapter($IPAM);
		}

		/**
		  * @param Addon\Ipam\Main|Addon\Ipam\Main[] $adapter
		  * @throw Core\Exception
		  * @return bool
		  */
		public static function setAdapter($adapter)
		{
			$status = parent::setAdapter($adapter);

			if($status) {
				self::$_IPAM = &self::$_adapter;
				self::$_aIPAM = &self::$_allAdapters;
			}

			return $status;
		}

		/**
		  * @return null|Addon\Ipam\Main|Addon\Ipam\Main[]
		  */
		public static function getIpam()
		{
			return self::getAdapter();
		}

		/**
		  * @param string $key
		  * @return bool
		  */
		public static function enableIpam($key)
		{
			return self::enableAdapter($key);
		}

		/**
		  * @return null|Addon\Ipam\Main
		  */
		public static function getIpamEnabled()
		{
			return self::getAdapterEnabled();
		}
	}