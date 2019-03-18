<?php
	namespace Addon\Ipam;

	abstract class Api_Subnet_Abstract extends Api_Abstract
	{
		/**
		  * @var false|Addon\Ipam\Api_Section
		  */
		protected $_sectionApi = null;

		/**
		  * @var false|Addon\Ipam\Api_Folder|Addon\Ipam\Api_Subnet
		  */
		protected $_parentSubnetApi = null;

		/**
		  * Path to self object
		  * @var array
		  */
		protected $_path = null;


		// @todo cache
		/*public function getSection()
		{
			$sectionId = $this->getSectionId();

			if($sectionId !== false) {
				return $this->_IPAM->getSection($sectionId);
			}
			else {
				return false;
			}
		}*/

		public function getSectionId()
		{
			return $this->_getField('sectionId', 'int&&>0', 'int');
		}

		public function getSectionApi()
		{
			if($this->_sectionApi === null)
			{
				$sectionId = $this->getSectionId();

				if($sectionId !== false) {
					$this->_sectionApi = new Api_Section($sectionId);
				}
				else {
					$this->_sectionApi = false;
				}
			}

			return $this->_sectionApi;
		}

		/**
		  * Get parent subnet (or folder)
		  * Do not filter subnet or folder
		  *
		  * @return false|array
		  */
		public function getParentSubnet()
		{
			$parentSubnetId = $this->getParentSubnetId();

			if($parentSubnetId !== false)
			{
				if(self::cacheEnabled($this->_IPAM))
				{
					$parentSubnets = Api_Folder::searchFolders('*', null, $this->getSectionId(), true, $this->_IPAM);
					$parentSubnets = $this->_filterObjects($parentSubnets, Api_Folder::FIELD_ID, (string) $parentSubnetId);

					if(count($parentSubnets) === 0) {
						$parentSubnets = Api_Subnet::searchSubnets('*', null, null, null, $this->getSectionId(), true, $this->_IPAM);
						$parentSubnets = $this->_filterObjects($parentSubnets, Api_Subnet::FIELD_ID, (string) $parentSubnetId);
					}

					if(count($parentSubnets) === 1) {
						return $parentSubnets[0];
					}
				}
				else
				{
					$parentSubnet = $this->_IPAM->getFolder($parentSubnetId);

					if($parentSubnet === false) {
						$parentSubnet = $this->_IPAM->getSubnet($parentSubnetId);
					}

					return $parentSubnet;
				}
			}

			return false;
		}

		public function getParentSubnetId()
		{
			return $this->_getField('masterSubnetId', 'int&&>0');
		}

		public function getParentSubnetApi()
		{
			if($this->_parentSubnetApi === null)
			{
				$parentSubnet = $this->getParentSubnet();

				if($parentSubnet !== false)
				{
					if((int) $parentSubnet['isFolder'] === 1) {
						$this->_parentSubnetApi = new Api_Folder($parentSubnet[Api_Folder::FIELD_ID]);
					}
					else {
						$this->_parentSubnetApi = new Api_Subnet($parentSubnet[Api_Subnet::FIELD_ID]);
					}
				}
				else {
					$this->_parentSubnetApi = false;
				}
			}

			return $this->_parentSubnetApi;
		}

		public function getPath($includeLabel = false, $pathSeparator = false)
		{
			if($this->_path === null) {
				$this->_path = $this->getPaths(false);
			}

			if($this->_path !== false)
			{
				$path = $this->_path;

				if($includeLabel && $this->hasObjectLabel()) {
					$path[] = $this->getObjectLabel();
				}

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
			if($this->objectExists())
			{			
				$objectApi = $this->getParentSubnetApi();

				if($objectApi !== false) {
					$path = $objectApi->getPaths(true);
				}
				else
				{
					$sectionId = $this->_getField('sectionId', 'int&&>0');

					if(Api_Section::objectIdIsValid($sectionId)) {
						$objectApi = new Api_Section($sectionId);
						$path = $objectApi->getPaths(true);
					}
				}

				if($includeLabel) {
					$path[] = $this->label;
				}

				return $path;
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
			$this->_resetSection();
			$this->_resetSubnet();
		}

		protected function _resetAttributes()
		{
			$this->_path = null;
		}

		protected function _resetSection()
		{
			$this->_sectionApi = null;
		}

		protected function _resetSubnet()
		{
			$this->_parentSubnetApi = null;
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'sectionApi':
				case 'parentSectionApi': {
					return $this->getSectionApi();
				}
				case 'subnetApi':
				case 'folderApi':
				case 'parentSubnetApi':
				case 'parentFolderApi': {
					return $this->getParentSubnetApi();
				}
				default: {
					return parent::__get($name);
				}
			}
		}

		public function __call($method, $parameters = null)
		{
			/*if(substr($method, 0, 3) === 'get')
			{
				$name = substr($method, 3);
				$name = mb_strtolower($name);

				switch($name)
				{
					case 'field': {
						return $this->_getField($name, 'string&&!empty');
					}
				}
			}*/

			return parent::__call($method, $parameters);
		}
	}