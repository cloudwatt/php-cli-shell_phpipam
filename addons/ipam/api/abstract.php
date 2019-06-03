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


		public static function objectIdIsValid($objectId)
		{
			return C\Tools::is('int&&>0', $objectId);
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
					return $this->_adapter;
				}
				default: {
					return parent::__get($name);
				}
			}
		}

		/**
		  * @return Addon\Ipam\Orchestrator
		  */
		protected static function _getOrchestrator()
		{
			return Orchestrator::getInstance();
		}
	}