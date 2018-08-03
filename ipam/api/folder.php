<?php
	class Ipam_Api_Folder extends Ipam_Api_Subnet_Abstract
	{
		const OBJECT_TYPE = 'folder';
		const FIELD_NAME = 'description';


		public function folderIdIsValid($folderId)
		{
			return $this->objectIdIsValid($folderId);
		}

		public function hasFolderId()
		{
			return $this->hasObjectId();
		}

		public function getFolderId()
		{
			return $this->getObjectId();
		}

		public function folderExists()
		{
			return $this->objectExists();
		}

		public function getFolderLabel()
		{
			return $this->getObjectLabel();
		}

		protected function _getObject()
		{
			if($this->_objectExists === null || $this->objectExists())
			{
				if($this->_objectDatas === null) {
					$this->_objectDatas = $this->_IPAM->getFolder($this->getFolderId());
				}

				return $this->_objectDatas;
			}
			else {
				return false;
			}
		}

		public function getSubFolders($folderName = null)
		{
			if($this->folderExists()) {
				$folders = $this->_IPAM->getFolders($this->getSectionId(), $this->getFolderId());
				return $this->_getSubObjects($folders, self::FIELD_NAME, $folderName);
			}

			return false;
		}

		public function getSubFolderId($folderName)
		{
			$folders = $this->getSubFolders($folderName);
			return ($folders !== false && count($folders) === 1) ? ($folders[0]['id']) : (false);
		}

		public function getSubnets()
		{
			if($this->folderExists()) {
				return $this->_IPAM->getSubnets($this->getSectionId(), $this->getFolderId());
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
	}