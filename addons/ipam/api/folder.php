<?php
	namespace Addon\Ipam;

	use Core as C;

	/**
	  * /!\ Folder has subnet and mask equal to null
	  * Do not use these fields, it is not a subnet!
	  */
	class Api_Folder extends Api_Subnet_Abstract
	{
		const OBJECT_KEY = 'FOLDER';
		const OBJECT_TYPE = 'folder';
		const OBJECT_NAME = 'folder';

		const FIELD_ID = 'id';
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
				if($this->_objectDatas === null)
				{
					$this->_objectDatas = false;
		
					/**
					  * @todo a décommenter après correction bug PHPIPAM
					  * curl -vvv -H "token: [token]" -H "Content-Type: application/json; charset=utf-8" https://ipam.corp.cloudwatt.com/api/myAPP/folders/1185/
					  * Fatal error</b>:  Unsupported operand types in <b>/opt/phpipam/functions/classes/class.Tools.php</b> on line <b>1695</b>
					$this->_objectDatas = $this->_adapter->getFolder($this->getFolderId());*/

					$folders = $this->_adapter->getAllFolders();

					foreach($folders as $folder)
					{
						if((int) $folder[self::FIELD_ID] === $this->getFolderId()) {
							$this->_objectDatas = $folder;
						}
					}
				}

				return $this->_objectDatas;
			}
			else {
				return false;
			}
		}

		public function getSubFolder($folderName)
		{
			$folders = $this->getSubFolders($folderName);
			return ($folders !== false && count($folders) === 1) ? ($folders[0]) : (false);
		}

		public function getSubFolderId($folderName)
		{
			$folder = $this->getSubFolder($folderName);
			return ($folder !== false) ? ($folder[self::FIELD_ID]) : (false);
		}

		public function getSubFolderApi($folderName)
		{
			$folderId = $this->getSubFolderId($folderName);
			return ($folderId !== false) ? (new self($folderId, $this->_service)) : (false);
		}

		public function getSubFolders($folderName = null)
		{
			if($this->folderExists()) {
				return self::searchFolders($folderName, $this->getFolderId(), null, true, $this->_adapter);
			}
			else {
				return false;
			}
		}

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
			return ($subnetId !== false) ? (new Api_Subnet($subnetId, $this->_service)) : (false);
		}

		public function getSubnets($subnetName = null)
		{
			if($this->folderExists()) {
				return Api_Subnet::searchSubnets($subnetName, null, null, $this->getFolderId(), null, true, $this->_adapter);
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
			if($this->folderExists()) {
				return Api_Subnet::searchSubnets($subnet, $IPv, null, $this->getFolderId(), null, $strict);
			}
			else {
				return false;
			}
		}

		/**
		  * Return all folders matches request
		  *
		  * All arguments must be optional
		  *
		  * @param string $folderName Folder label, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array
		  */
		public function getFolders($folderName = '*', $strict = false)
		{
			return $this->findFolders($folderName, $strict);
		}

		/**
		  * Return all folders matches request
		  *
		  * @param string $folderName Folder label, wildcard * is allowed
		  * @param bool $strict
		  * @return false|array
		  */
		public function findFolders($folderName, $strict = false)
		{
			if($this->hasFolderId()) {
				return self::_searchFolders($this->_adapter, $folderName, $this->getFolderId(), null, $strict);
			}
			else {
				return false;
			}
		}

		/**
		  * Return all folders matches request
		  *
		  * @param string $folderName Folder label, wildcard * is allowed
		  * @param int $folderId Folder ID
		  * @param int $sectionId Section ID
		  * @param bool $strict
		  * @param Addon\Ipam\Adapter $IPAM IPAM adapter
		  * @return false|array
		  */
		public static function searchFolders($folderName, $folderId = null, $sectionId = null, $strict = false, Adapter $IPAM = null)
		{
			return self::_searchFolders($IPAM, $folderName, $folderId, $sectionId, $strict);
		}

		/**
		  * Return all folders matches request
		  *
		  * @param Addon\Ipam\Adapter $IPAM IPAM adapter
		  * @param string $folderName Folder label, wildcard * is allowed
		  * @param int $folderId Folder ID
		  * @param int $sectionId Section ID
		  * @param bool $strict
		  * @return false|array
		  */
		protected static function _searchFolders(Adapter $IPAM = null, $folderName = '*', $folderId = null, $sectionId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = self::_getAdapter();
			}

			if(($folders = self::_getSelfCache(self::OBJECT_TYPE, $IPAM)) !== false)
			{
				if(C\Tools::is('int&&>=0', $folderId)) {
					$folders = self::_filterObjects($folders, 'masterSubnetId', (string) $folderId);
				}

				if(C\Tools::is('int&&>=0', $sectionId)) {
					$folders = self::_filterObjects($folders, 'sectionId', (string) $sectionId);
				}

				return self::_searchObjects($folders, self::FIELD_NAME, $folderName, $strict);
			}
			else {
				return $IPAM->searchFolderName($folderName, $folderId, $sectionId, $strict);
			}
		}
	}