<?php
	abstract class Ipam_Api_Subnet_Abstract extends Ipam_Api_Abstract
	{
		protected $_sectionApi;
		protected $_parentSubnetApi;


		public function getSectionId()
		{
			return $this->_getField('sectionId', 'int&&>0');
		}

		public function getSection()
		{
			$sectionId = $this->getSectionId();

			if($sectionId !== false) {
				return $this->_IPAM->getSection($sectionId);
			}
			else {
				return false;
			}
		}

		public function getSectionApi()
		{
			if($this->_sectionApi === null)
			{
				$sectionId = $this->getSectionId();

				if($sectionId !== false) {
					$this->_sectionApi = new Ipam_Api_Section($sectionId);
				}
				else {
					$this->_sectionApi = false;
				}
			}

			return $this->_sectionApi;
		}

		public function getParentSubnetId()
		{
			return $this->_getField('masterSubnetId', 'int&&>0');
		}

		public function getParentSubnet()
		{
			$parentSubnetId = $this->getParentSubnetId();

			if($parentSubnetId !== false) {
				return $this->_IPAM->getSubnet($parentSubnetId);
			}
			else {
				return false;
			}
		}

		public function getParentSubnetApi()
		{
			if($this->_parentSubnetApi === null)
			{
				$parentSubnetId = $this->getParentSubnetId();

				if($parentSubnetId !== false) {
					$this->_parentSubnetApi = new Ipam_Api_Subnet($parentSubnetId);
				}
				else {
					$this->_parentSubnetApi = false;
				}
			}

			return $this->_parentSubnetApi;
		}

		public function getPath()
		{
			$path = $this->_getPath();
			array_pop($path);
			return $path;
		}

		/**
		  * @todo a supprimer après correction bug PHPIPAM
		  * curl -vvv -H "token: [token]" -H "Content-Type: application/json; charset=utf-8" https://ipam.corp.cloudwatt.com/api/myAPP/folders/1185/
		  * Fatal error</b>:  Unsupported operand types in <b>/opt/phpipam/functions/classes/class.Tools.php</b> on line <b>1695</b>
		  */
		protected function _getPath()
		{
			$subnetId = $this->_getField('masterSubnetId', 'int&&>0');
			$isFolder = (bool) $this->_getField('isFolder', 'binary');

			if($this->objectIdIsValid($subnetId))
			{
				if(!$isFolder) {
					$objectApi = new Ipam_Api_Subnet($subnetId);
				}
				else {
					$objectApi = new Ipam_Api_Folder($subnetId);
				}

				$path = $objectApi->_getPath();
			}
			else
			{
				$sectionId = $this->_getField('sectionId', 'int&&>0');

				if(Ipam_Api_Section::objectIdIsValid($sectionId)) {
					$objectApi = new Ipam_Api_Section($sectionId);
					$path = $objectApi->getPath();
				}
				else {
					$subnetLabel = $this->getSubnetLabel();
					if($subnetLabel === false) { $subnetLabel = 'ROOT'; }	//@todo a supprimer une fois le bug Unsupported operand types corrigé
					return array($subnetLabel);
				}
			}

			$path[] = $this->getSubnetLabel();
			return $path;
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'sectionApi': {
					return $this->getSectionApi();
				}
				case 'subnetApi':
				case 'parentSubnetApi': {
					return $this->getParentSubnetApi();
				}
				default: {
					return parent::__get($name);
				}
			}
		}
	}