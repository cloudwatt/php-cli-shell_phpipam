<?php
	namespace Addon\Ipam;

	use Core as C;

	class Api_Section extends Api_Abstract
	{
		const OBJECT_KEY = 'SECTION';
		const OBJECT_TYPE = 'section';
		const OBJECT_NAME = 'section';

		const FIELD_ID = 'id';
		const FIELD_NAME = 'name';
		const FIELD_DESC = 'description';

		/**
		  * Path to self object
		  * @var array
		  */
		protected $_path = null;


		public function sectionIdIsValid($sectionId)
		{
			return $this->objectIdIsValid($sectionId);
		}

		public function hasSectionId()
		{
			return $this->hasObjectId();
		}

		public function getSectionId()
		{
			return $this->getObjectId();
		}

		public function sectionExists()
		{
			return $this->objectExists();
		}

		public function setSectionLabel($locationLabel)
		{
			return $this->_setObjectLabel($locationLabel);
		}

		public function hasSectionLabel()
		{
			return $this->hasObjectLabel();
		}

		public function getSectionLabel()
		{
			return $this->getObjectLabel();
		}

		protected function _getObject()
		{
			if($this->_objectExists === null || $this->objectExists())
			{
				if($this->_objectDatas === null) {
					$this->_objectDatas = $this->_adapter->getSection($this->getSectionId());
				}

				return $this->_objectDatas;
			}
			else {
				return false;
			}
		}

		public function getPath($includeLabel = false, $pathSeparator = false)
		{
			$path = $this->getPaths($includeLabel);

			if($path !== false)
			{
				if($pathSeparator === false) {
					$pathSeparator = self::SEPARATOR_PATH;
				}

				return implode($pathSeparator, $path);
			}
			else {
				return false;
			}
		}

		public function getPaths($includeLabel = false)
		{
			if($this->sectionExists())
			{
				if($this->_path === null)
				{
					$sectionId = $this->_getField('masterSection', 'int&&>=0');

					if($this->objectIdIsValid($sectionId)) {
						$sectionApi = new Api_Section($sectionId);
						$this->_path = $sectionApi->getPaths(true);
					}
					else {
						$this->_path = array();
					}
				}

				if($this->_path !== false)
				{
					$path = $this->_path;

					if($includeLabel && $this->hasSectionLabel()) {
						$path[] = $this->getSectionLabel();
					}

					return $path;
				}
			}
			elseif($includeLabel && $this->hasSectionLabel()) {
				return array($this->getSectionLabel());
			}

			return false;
		}

		/**
		  * Section name must be unique
		  * Return false if more than one section found
		  *
		  * @var string $sectionName
		  * @return false|array
		  */
		public function getSubSection($sectionName)
		{
			$sections = $this->getSubSections($sectionName);
			return ($sections !== false && count($sections) === 1) ? ($sections[0]) : (false);
		}

		public function getSubSectionId($sectionName)
		{
			$section = $this->getSubSection($sectionName);
			return ($section !== false) ? ($section[self::FIELD_ID]) : (false);
		}

		public function getSubSectionApi($sectionName)
		{
			$sectionId = $this->getSubSectionId($sectionName);
			return ($sectionId !== false) ? (new self($sectionId)) : (false);
		}

		public function getSubSections($sectionName = null)
		{
			if($this->sectionExists())
			{
				if(($sections = $this->_getThisCache(self::OBJECT_TYPE)) !== false) {
					$sections = self::_filterObjects($sections, 'masterSection', (string) $this->getSectionId());
					return self::_filterObjects($sections, self::FIELD_NAME, $sectionName);
				}
				else {
					$sections = $this->_adapter->getSections($this->getSectionId());
					return $this->_filterObjects($sections, self::FIELD_NAME, $sectionName);
				}
			}
			else {
				return $this->getRootSections($sectionName, true);
			}
		}

		/**
		  * Folder name is not unique (IPv4 and IPv6)
		  * Return false if more than one folder found
		  *
		  * @var string $folderName
		  * @return false|array
		  */
		public function getFolder($folderName)
		{
			$folders = $this->getFolders($folderName);
			return ($folders !== false && count($folders) === 1) ? ($folders[0]) : (false);
		}

		public function getFolderId($folderName)
		{
			$folder = $this->getFolder($folderName);
			return ($folder !== false) ? ($folder[Api_Folder::FIELD_ID]) : (false);
		}

		public function getFolderApi($folderName)
		{
			$folderId = $this->getFolderId($folderName);
			return ($folderId !== false) ? (new Api_Folder($folderId)) : (false);
		}

		public function getFolders($folderName = null)
		{
			if($this->sectionExists()) {
				return Api_Folder::searchFolders($folderName, null, $this->getSectionId(), true, $this->_adapter);
			}
			else {
				return false;
			}
		}

		/**
		  * Return all folders matches request
		  *
		  * @param string $folderName Folder label, wildcard * is allowed
		  * @param int $IPv IP version, 4 or 6
		  * @param bool $strict
		  * @return false|array
		  */
		public function findFolders($folderName, $strict = false)
		{
			if($this->sectionExists()) {
				return Api_Folder::searchFolders($folderName, null, $this->getSectionId(), $strict, $this->_adapter);
			}
			else {
				return false;
			}
		}

		/**
		  * Subnet name is not unique  (IPv4 and IPv6)
		  * Return false if more than one subnet found
		  *
		  * @var string $folderName
		  * @return false|array
		  */
		public function getSubnet($subnetName)
		{
			$subnets = $this->getSubnets($subnetName);
			return ($subnets !== false && count($subnets) === 1) ? ($subnets[0]) : (false);
		}

		public function getSubnetId($subnetName)
		{
			$subnet = $this->getSubnet($subnetName);
			return ($subnet !== false) ? ($subnet[Api_Subnet::FIELD_ID]) : (false);
		}

		public function getSubnetApi($subnetName)
		{
			$subnetId = $this->getSubnetId($subnetName);
			return ($subnetId !== false) ? (new Api_Subnet($subnetId)) : (false);
		}

		public function getSubnets($subnetName = null)
		{
			if($this->sectionExists()) {
				return Api_Subnet::searchSubnets($subnetName, null, null, null, $this->getSectionId(), true, $this->_adapter);
			}
			else {
				return false;
			}
		}

		/**
		  * Return all subnets matches request
		  *
		  * @param string $subnet Subnet CIDR or name, wildcard * is allowed for name only
		  * @param int $IPv IP version, 4 or 6
		  * @param bool $strict
		  * @return false|array
		  */
		public function findSubnets($subnet, $IPv = null, $strict = false)
		{
			if($this->sectionExists()) {
				return Api_Subnet::searchSubnets($subnet, $IPv, null, 0, $this->getSectionId(), $strict, $this->_adapter);
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
			$this->_path = null;
		}

		public function __get($name)
		{
			switch($name)
			{
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
					case 'description': {
						return $this->_getField(self::FIELD_DESC, 'string&&!empty');
					}
				}
			}

			return parent::__call($method, $parameters);
		}

		/**
		  * Return all root sections matches request
		  *
		  * All arguments must be optional
		  *
		  * @param string $sectionName Section label, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array
		  */
		public function getRootSections($sectionName = null, $strict = false)
		{
			return $this->findRootSections($sectionName, $strict);
		}

		/**
		  * Return all root sections matches request
		  *
		  * @param string $sectionName Section label, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array
		  */
		public function findRootSections($sectionName, $strict = false)
		{
			return self::searchRootSections($sectionName, $strict, $this->_adapter);
		}

		/**
		  * Return all root sections matches request
		  *
		  * @param string $sectionName Section label, wildcard * is allowed
		  * @param bool $strict
		  * @param Addon\Ipam\Adapter $IPAM IPAM adapter
		  * @return false|array
		  */
		public static function searchRootSections($sectionName, $strict = false, Adapter $IPAM = null)
		{
			if($IPAM === null) {
				$IPAM = self::_getAdapter();
			}

			if(($sections = self::_getSelfCache(self::OBJECT_TYPE, $IPAM)) !== false) {
				$sections = self::_filterObjects($sections, 'masterSection', (string) $IPAM::SECTION_ROOT_ID);
				return self::_searchObjects($sections, self::FIELD_NAME, $sectionName, $strict);
			}
			else {
				return $IPAM->searchSectionName($sectionName, $IPAM::SECTION_ROOT_ID, $strict);
			}
		}

		/**
		  * Return all sections matches request
		  *
		  * All arguments must be optional
		  *
		  * @param string $sectionName Section label, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array
		  */
		public function getSections($sectionName = '*', $strict = false)
		{
			return $this->findSections($sectionName, $strict);
		}

		/**
		  * Return all sections matches request
		  *
		  * @param string $sectionName Section label, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array
		  */
		public function findSections($sectionName, $strict = false)
		{
			if($this->hasSectionId()) {
				return self::_searchSections($this->_adapter, $sectionName, $this->getSectionId(), $strict);
			}
			else {
				return $this->findRootSections($sectionName, $strict);
			}
		}

		/**
		  * Return all sections matches request
		  *
		  * Ne pas rechercher que les sections root si sectionId est égale à null
		  *
		  * @param string $sectionName Section label, wildcard * is allowed
		  * @param int $sectionId Section ID
		  * @param bool $strict
		  * @return false|array
		  */
		public static function searchSections($sectionName, $sectionId = null, $strict = false)
		{
			return self::_searchSections(null, $sectionName, $sectionId, $strict);
		}

		/**
		  * Return all sections matches request
		  *
		  * @param Addon\Ipam\Adapter $IPAM IPAM adapter
		  * @param string $sectionName Section label, wildcard * is allowed
		  * @param int $sectionId Section ID
		  * @param bool $strict
		  * @return false|array
		  */
		protected static function _searchSections(Adapter $IPAM = null, $sectionName = '*', $sectionId = null, $strict = false)
		{
			return self::_searchSectionNames($IPAM, $sectionName, $sectionId, $strict);
		}

		public function findSectionNames($sectionName, $sectionId = null, $strict = false)
		{
			return self::_searchSectionNames($this->_adapter, $sectionName, $sectionId, $strict);
		}

		public static function searchSectionNames($sectionName, $sectionId = null, $strict = false)
		{
			return self::_searchSectionNames(null, $sectionName, $sectionId, $strict);
		}

		protected static function _searchSectionNames(Adapter $IPAM = null, $sectionName = '*', $sectionId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = self::_getAdapter();
			}

			if(($sections = self::_getSelfCache(self::OBJECT_TYPE, $IPAM)) !== false)
			{
				if(C\Tools::is('int&&>=0', $sectionId)) {
					$sections = self::_filterObjects($sections, 'masterSection', (string) $sectionId);
				}

				return self::_searchObjects($sections, self::FIELD_NAME, $sectionName, $strict);
			}
			else {
				return $IPAM->searchSectionName($sectionName, $sectionId, $strict);
			}
		}
	}