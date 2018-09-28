<?php
	class Ipam_Api_Section extends Ipam_Api_Abstract
	{
		const OBJECT_TYPE = 'section';
		const FIELD_NAME = 'name';

		protected static $_sections = array();


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

		public function getSectionLabel()
		{
			return $this->getObjectLabel();
		}

		protected function _getObject()
		{
			if($this->_objectExists === null || $this->objectExists())
			{
				if($this->_objectDatas === null) {
					$this->_objectDatas = $this->_IPAM->getSection($this->getSectionId());
				}

				return $this->_objectDatas;
			}
			else {
				return false;
			}
		}

		public function getName()
		{
			return $this->_getField('name', 'string&&!empty');
		}

		public function getDescription()
		{
			return $this->_getField('description', 'string&&!empty');
		}

		public function getPath()
		{
			$sectionId = $this->_getField('masterSection', 'int&&>=0');

			if($this->objectIdIsValid($sectionId))
			{
				$objectApi = new Ipam_Api_Section($sectionId);

				$path = $objectApi->getPath();
				$path[] = $this->getSubnetLabel();
				return $path;
			}
			else {
				$sectionLabel = $this->getSectionLabel();
				return array($sectionLabel);
			}
		}

		public function getSubSections($sectionName = null)
		{
			if($this->sectionExists()) {
				$sections = $this->_IPAM->getSections($this->getSectionId());
			}
			else {
				$sections = $this->getRootSections();
			}

			return $this->_getSubObjects($sections, self::FIELD_NAME, $sectionName);
		}

		public function getSubSectionId($sectionName)
		{
			$sections = $this->getSubSections($sectionName);
			return ($sections !== false && count($sections) === 1) ? ($sections[0]['id']) : (false);
		}

		public function getFolders()
		{
			if($this->sectionExists()) {
				return $this->_IPAM->getRootFolders($this->getSectionId());
			}
			else {
				return false;
			}
		}

		public function getFolderId($folderName)
		{
			$folders = $this->getFolders();
			return $this->_getObjectId($folders, Ipam_Api_Folder::FIELD_NAME, $folderName);
		}

		public function getSubnets()
		{
			if($this->sectionExists()) {
				return $this->_IPAM->getRootSubnets($this->getSectionId());
			}
			else {
				return false;
			}
		}

		public function getSubnetId($subnetName)
		{
			$subnets = $this->getSubnets();
			return $this->_getObjectId($subnets, Ipam_Api_Subnet::FIELD_NAME, $subnetName);
		}

		public function getRootSections()
		{
			return $this->findRootSections();
		}

		public function findRootSections()
		{
			return self::_getSections($this->_IPAM);
		}

		public static function searchRootSections()
		{
			return self::_getSections();
		}

		public function findSectionNames($sectionName, $strict = false)
		{
			$sections = self::_getSections($this->_IPAM);
			return self::_searchObjects($sections, self::FIELD_NAME, $sectionName, $strict);
		}

		public static function searchSectionNames($sectionName, $strict = false)
		{
			$sections = self::_getSections();
			return self::_searchObjects($sections, self::FIELD_NAME, $sectionName, $strict);
		}

		protected static function _getSections(IPAM_Main $IPAM = null)
		{
			if($IPAM === null) {
				$IPAM = self::$_IPAM;
			}

			$id = $IPAM->getServerId();

			if(!array_key_exists($id, self::$_sections)) {
				self::$_sections[$id] = $IPAM->getAllSections();
			}

			return self::$_sections[$id];
		}
	}