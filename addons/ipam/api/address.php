<?php
	namespace Addon\Ipam;

	use Core as C;
	use Core\Exception as E;

	class Api_Address extends Api_Abstract
	{
		const OBJECT_KEY = 'ADDRESS';
		const OBJECT_TYPE = 'address';
		const OBJECT_NAME = 'address';

		const FIELD_ID = 'id';
		const FIELD_NAME = 'hostname';
		const FIELD_DESC = 'description';

		const TAGS = array(
			1 => 'offline',
			2 => 'online',
			3 => 'reserved',
			4 => 'DHCP',
		);

		const OFFLINE = 'offline';
		const ONLINE = 'online';
		const RESERVED = 'reserved';
		const DHCP = 'DHCP';

		/**
		  * @var int
		  */
		protected $_subnetId;

		/**
		  * @var Addon\Ipam\Api_Subnet
		  */
		protected $_subnetApi;

		/**
		  * @var string
		  */
		protected $_address;


		public function addressIdIsValid($addressId)
		{
			return $this->objectIdIsValid($addressId);
		}

		public function hasAddressId()
		{
			return $this->hasObjectId();
		}

		public function getAddressId()
		{
			return $this->getObjectId();
		}

		public function addressExists()
		{
			return $this->objectExists();
		}

		public function setAddressLabel($addressLabel)
		{
			return $this->_setObjectLabel($addressLabel);
		}

		public function hasAddressLabel()
		{
			return $this->hasObjectLabel();
		}

		public function getAddressLabel()
		{
			return $this->getObjectLabel();
		}

		protected function _getObject()
		{
			if($this->_objectExists === null || $this->objectExists())
			{
				if($this->_objectDatas === null) {
					$this->_objectDatas = $this->_adapter->getAddress($this->getAddressId());
				}

				return $this->_objectDatas;
			}
			else {
				return false;
			}
		}

		public function isIPv($IPv)
		{
			switch($IPv)
			{
				case 4: {
					return $this->isIPv4();
				}
				case 6: {
					return $this->isIPv6();
				}
				default: {
					return false;
				}
			}
		}

		public function isIPv4()
		{
			$address = $this->getAddress();
			return Tools::isIPv4($address);
		}

		public function isIPv6()
		{
			$address = $this->getAddress();
			return Tools::isIPv6($address);
		}

		public function hasSubnetId()
		{
			return ($this->getSubnetId() !== false);
		}

		public function setSubnetId($subnetId)
		{
			if(!$this->objectExists() && C\Tools::is('int&&>0', $subnetId)) {
				$this->_subnetId = $subnetId;
				return true;
			}
			else {
				return false;
			}
		}

		public function setSubnetApi(Api_Subnet $subnetApi)
		{
			if($subnetApi->subnetExists())
			{
				$status = $this->setSubnetId($subnetApi->id);

				if($status) {
					$this->_subnetApi = $subnetApi;
				}

				return $status;
			}
			else {
				return false;
			}
		}

		public function getSubnet()
		{
			$subnetId = $this->getSubnetId();

			if($subnetId !== false) {
				return $this->_adapter->getSubnet($subnetId);
			}
			else {
				return false;
			}
		}

		public function getSubnetId()
		{
			if($this->addressExists())
			{
				if($this->_subnetId === null) {
					$this->_subnetId = $this->_getField('subnetId', 'int&&>0');
				}

				return $this->_subnetId;
			}
			elseif($this->_subnetId !== null) {
				return $this->_subnetId;
			}
			else {
				return false;
			}
		}

		public function getSubnetApi()
		{
			if($this->_subnetApi === null)
			{
				$subnetId = $this->getSubnetId();

				if($subnetId !== false) {
					$this->_subnetApi = new Api_Subnet($subnetId);
				}
				else {
					$this->_subnetApi = false;
				}
			}

			return $this->_subnetApi;
		}

		public function hasAddress()
		{
			return ($this->getAddress() !== false);
		}

		public function setAddress($address)
		{
			if(!$this->objectExists() && $this->hasSubnetId() && Tools::isIP($address))
			{
				if(($this->getSubnetApi()->isIPv4() && Tools::isIPv4($address) && Tools::cidrMatch($address, $this->getSubnetApi()->subnet)) ||
					($this->getSubnetApi()->isIPv6() && Tools::isIPv6($address) && Tools::cidrMatch($address, $this->getSubnetApi()->subnet)))
				{
					$this->_address = $address;
					return true;
				}
			}

			return false;
		}

		public function getAddress()
		{
			if($this->addressExists())
			{
				if($this->_address === null) {
					$this->_address = $this->_getField('ip', 'string&&!empty');
				}

				return $this->_address;
			}
			elseif($this->_address !== null) {
				return $this->_address;
			}
			else {
				return false;
			}
		}

		public function create($description = '', $note = '', $port = '', $tag = self::ONLINE, $autoRegisterToStore = true)
		{
			$this->_errorMessage = null;

			if(!$this->addressExists())
			{
				if($this->hasSubnetId())
				{
					if($this->hasAddress())
					{
						if($this->hasAddressLabel())
						{
							if(!in_array($tag, self::TAGS, true)) {
								$tag = null;
							}

							try {
								$status = $this->_adapter->createAddress($this->getSubnetId(), $this->getAddress(), $this->getHostname(), $description, $note, $port, $tag);
							}
							catch(E\Message $e) {
								$this->_errorMessage = $e->getMessage();
								$status = false;
							}

							if($status)
							{
								$addresses = $this->findIpAddresses($this->getAddress(), $this->getSubnetId(), true);

								if($addresses !== false && count($addresses) === 1)
								{
									$addressId = $addresses[0][self::FIELD_ID];
									$this->_hardReset(false);
									$this->_setObjectId($addressId);

									if($autoRegisterToStore) {
										$this->_registerToStore();
									}
								}
								else {
									$status = false;
								}
							}

							if(!$status) {
								$this->_hardReset(false);
							}

							return $status;
						}
						else {
							$this->_errorMessage = "Address hostname is required";
						}
					}
					else {
						$this->_errorMessage = "Address IP is required";
					}
				}
				else {
					$this->_errorMessage = "Address subnet is required";
				}
			}
			else {
				$this->_errorMessage = "Address '".$this->label."' already exists";
			}

			return false;
		}

		/*public function modify($description = '', $note = '', $port = '', $tag = self::ONLINE)
		{
			// @todo a coder
		}*/

		/**
		  * @param string $label
		  * @return bool
		  */
		public function renameHostname($label)
		{
			return $this->_updateInfos($label, $this->description);
		}

		/**
		  * @param string $description
		  * @return bool
		  */
		public function setDescription($description)
		{
			return $this->_updateInfos($this->label, $description);
		}

		/**
		  * @param string $label
		  * @param string $description
		  * @return bool
		  */
		public function updateInfos($label, $description)
		{
			return $this->_updateInfos($label, $description);
		}

		/**
		  * @param string $label
		  * @param string $description
		  * @return bool
		  */
		protected function _updateInfos($label, $description)
		{
			$this->_errorMessage = null;

			if($this->addressExists())
			{
				try {
					$status = $this->_adapter->modifyAddress($this->getAddressId(), $label, $description);
				}
				catch(E\Message $e) {
					$this->_errorMessage = $e->getMessage();
					$status = false;
				}

				$this->refresh();
				return $status;
			}
			else {
				$this->_errorMessage = "IPAM address does not exist";
				return false;
			}
		}

		public function remove()
		{
			$this->_errorMessage = null;

			if($this->addressExists())
			{
				try {
					$status = $this->_adapter->removeAddress($this->getAddressId());
				}
				catch(E\Message $e) {
					$this->_errorMessage = $e->getMessage();
					$status = false;
				}

				$this->_unregisterFromStore();
				$this->_hardReset();
				return $status;
			}
			else {
				$this->_errorMessage = "IPAM address does not exist";
			}

			return false;
		}

		/**
		  * @param bool $resetObjectId
		  * @return void
		  */
		protected function _hardReset($resetObjectId = false)
		{
			$this->_softReset($resetObjectId);
			$this->_resetAttributes();
			$this->_resetSubnet();
		}

		protected function _resetAttributes()
		{
			$this->_address = null;
		}

		protected function _resetSubnet()
		{
			$this->_subnetId = null;
			$this->_subnetApi = null;
		}

		public function __get($name)
		{
			switch($name)
			{
				case 'ip':
				case 'address': {
					return $this->getAddress();
				}
				case 'hostname': {
					return $this->getAddressLabel();
				}
				case 'description': {
					return $this->_getField(self::FIELD_DESC, 'string');
				}
				case 'tag': {
					return $this->_getField($name, 'string&&!empty');
				}
				case 'note': {
					return $this->_getField($name, 'string');
				}
				case 'subnetApi': {
					return $this->getSubnetApi();
				}
				case 'vlanApi': {
					$subnetApi = $this->getSubnetApi();
					return ($subnetApi !== false) ? ($subnetApi->vlanApi) : (false);
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
					case 'ip':
					case 'address': {
						return $this->getAddress();
					}
					case 'hostname': {
						return $this->getAddressLabel();
					}
					case 'description': {
						return $this->_getField(self::FIELD_DESC, 'string');
					}
					case 'tag': {
						return $this->_getField($name, 'string&&!empty');
					}
					case 'note': {
						return $this->_getField($name, 'string');
					}
				}
			}

			return parent::__call($method, $parameters);
		}

		/**
		  * Return all addresses matches request
		  *
		  * All arguments must be optional
		  *
		  * @param string $address Address IP or name, wildcard * is allowed
		  * @param int $IPv IP version, 4 or 6
		  * @param bool $strict
		  * @return false|array
		  */
		public function getAddresses($address = '*', $IPv = null, $strict = false)
		{
			return $this->findAddresses($address, $IPv, $strict);
		}

		/**
		  * Return all addresses matches request
		  *
		  * @param string $address Address IP or name, wildcard * is allowed
		  * @param int $IPv IP version, 4 or 6
		  * @param bool $strict
		  * @return false|array
		  */
		public function findAddresses($address, $IPv = null, $strict = false)
		{
			if($this->hasAddressId()) {
				return self::_searchAddresses($this->_adapter, $address, $IPv, $this->getSubnetId(), $strict);
			}
			else {
				return false;
			}
		}

		/**
		  * Return all addresses matches request
		  *
		  * @param string $address Address IP or name, wildcard * is allowed
		  * @param int $IPv IP version, 4 or 6
		  * @param int $subnetId Subnet ID
		  * @param bool $strict
		  * @return false|array
		  */
		public static function searchAddresses($address, $IPv = null, $subnetId = null, $strict = false)
		{
			return self::_searchAddresses(null, $address, $IPv, $subnetId, $strict);
		}

		/**
		  * Return all addresses matches request
		  *
		  * @param Addon\Ipam\Adapter $IPAM IPAM adapter
		  * @param string $address Address IP or name, wildcard * is allowed
		  * @param int $IPv IP version, 4 or 6
		  * @param int $subnetId Subnet ID
		  * @param bool $strict
		  * @return false|array
		  */
		protected static function _searchAddresses(Adapter $IPAM = null, $address = '*', $IPv = null, $subnetId = null, $strict = false)
		{
			if(Tools::isIP($address)) {
				return self::_searchIpAddresses($IPAM, $address, $subnetId, $strict);
			}
			else
			{
				$addresses = self::_searchAddressNames($IPAM, $address, $IPv, $subnetId, $strict);

				if(!C\Tools::is('array&&count>0', $addresses)) {
					$addresses = self::_searchAddressDescs($IPAM, $address, $IPv, $subnetId, $strict);
				}

				return $addresses;
			}
		}

		public function findIpAddresses($ip, $subnetId = null, $strict = false)
		{
			return self::_searchIpAddresses($this->_adapter, $ip, $subnetId, $strict);
		}

		public static function searchIpAddresses($ip, $subnetId = null, $strict = false)
		{
			return self::_searchIpAddresses(null, $ip, $subnetId, $strict);
		}

		// $strict for future use
		protected static function _searchIpAddresses(Adapter $IPAM = null, $ip = '*', $subnetId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = self::_getAdapter();
			}

			return $IPAM->searchAddressIP($ip, $subnetId, $strict);
		}

		public function findAddressNames($name, $IPv = null, $subnetId = null, $strict = false)
		{
			return self::_searchAddressNames($this->_adapter, $name, $IPv, $subnetId, $strict);
		}

		public static function searchAddressNames($name, $IPv = null, $subnetId = null, $strict = false)
		{
			return self::_searchAddressNames(null, $name, $IPv, $subnetId, $strict);
		}

		protected static function _searchAddressNames(Adapter $IPAM = null, $name = '*', $IPv = null, $subnetId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = self::_getAdapter();
			}

			if($name === null) {
				$name = '*';
			}

			$sectionNameFilter = null;

			if($subnetId === null)
			{
				$addresses = array();
				$separator = preg_quote(self::SEPARATOR_SECTION, '#');
				$status = preg_match('#(?:'.$separator.'(?<section>.+?)'.$separator.')?(?<name>.+)#i', $name, $nameParts);

				if($status && C\Tools::is('string&&!empty', $nameParts['section']) && C\Tools::is('string&&!empty', $nameParts['name'])) {
					$sectionNameFilter = $nameParts['section'];
					$name = $nameParts['name'];
				}
			}

			$addresses = $IPAM->searchAddHostname($name, $IPv, $subnetId, $strict);
			$addresses = self::_filterAddressOnSectionName($addresses, self::FIELD_NAME, $sectionNameFilter);

			return $addresses;
		}

		public function findAddressDescs($desc, $IPv = null, $subnetId = null, $strict = false)
		{
			return self::_searchAddressDescs($this->_adapter, $desc, $IPv, $subnetId, $strict);
		}

		public static function searchAddressDescs($desc, $IPv = null, $subnetId = null, $strict = false)
		{
			return self::_searchAddressDescs(null, $desc, $IPv, $subnetId, $strict);
		}

		protected static function _searchAddressDescs(Adapter $IPAM = null, $desc = '*', $IPv = null, $subnetId = null, $strict = false)
		{
			if($IPAM === null) {
				$IPAM = self::_getAdapter();
			}

			if($desc === null) {
				$desc = '*';
			}

			$sectionNameFilter = null;

			if($subnetId === null)
			{
				$addresses = array();
				$separator = preg_quote(self::SEPARATOR_SECTION, '#');
				$status = preg_match('#(?:'.$separator.'(?<section>.+?)'.$separator.')?(?<desc>.+)#i', $desc, $descParts);

				if($status && C\Tools::is('string&&!empty', $descParts['section']) && C\Tools::is('string&&!empty', $descParts['desc'])) {
					$sectionNameFilter = $descParts['section'];
					$desc = $descParts['desc'];
				}
			}

			$addresses = $IPAM->searchAddDescription($desc, $IPv, $subnetId, $strict);
			$addresses = self::_filterAddressOnSectionName($addresses, self::FIELD_DESC, $sectionNameFilter);

			return $addresses;
		}

		protected static function _filterAddressOnSectionName(array $addresses, $field, $sectionNameFilter = null)
		{
			if(C\Tools::is('string&&!empty', $sectionNameFilter))
			{
				$sections = Api_Section::searchSections($sectionNameFilter, null, true);

				if($sections !== false && count($sections) === 1)
				{
					$sectionId = (int) $sections[0][Api_Section::FIELD_ID];
					$sectionName = $sections[0][Api_Section::FIELD_NAME];

					foreach($addresses as $index => $address)
					{
						$Api_Subnet = new Api_Subnet($address['subnetId']);
						$subnetSectionId = (int) $Api_Subnet->getSectionId();

						if($subnetSectionId !== $sectionId) {
							unset($addresses[$index]);
						}
						else {
							$addresses[$index]['sectionId'] = $subnetSectionId;
						}
					}

					foreach($addresses as &$address) {
						$addressNamePrefix = self::SEPARATOR_SECTION.$sectionName.self::SEPARATOR_SECTION;
						$address[$field] = $addressNamePrefix.$address[$field];
					}
					unset($address);
				}
			}

			return $addresses;
		}
	}