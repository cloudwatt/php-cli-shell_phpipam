<?php
	namespace Addon\Ipam;

	use Core as C;

	class Service_Cache extends C\Addon\Service_Cache
	{
		/**
		  * @return bool
		  */
		public function initialization()
		{
			$this->newContainer(Api_Section::OBJECT_TYPE);
			$this->newContainer(Api_Folder::OBJECT_TYPE);
			$this->newContainer(Api_Subnet::OBJECT_TYPE);
			$this->newContainer(Api_Vlan::OBJECT_TYPE);
			$this->newContainer(Api_Address::OBJECT_TYPE);
			return true;
		}

		/**
		  * @param string $type
		  * @return Addon\Ipam\Service_CacheContainer
		  */
		protected function _newContainer($type)
		{
			return new Service_CacheContainer($this->_service, $type, false);
		}

		/**
		  * @param string $type
		  * @return bool
		  */
		protected function _refresh($type)
		{
			switch($type)
			{
				case Api_Section::OBJECT_TYPE: {
					return $this->refreshSections();
				}
				case Api_Folder::OBJECT_TYPE: {
					return $this->refreshFolders();
				}
				case Api_Subnet::OBJECT_TYPE: {
					return $this->refreshSubnets();
				}
				case Api_Vlan::OBJECT_TYPE: {
					return $this->refreshVlans();
				}
				case Api_Address::OBJECT_TYPE: {
					return $this->refreshAddresses();
				}
				default: {
					return false;
				}
			}
		}

		/**
		  * @return bool
		  */
		public function refreshSections()
		{
			if($this->isEnabled() && $this->cleaner(Api_Section::OBJECT_TYPE))
			{
				$sections = $this->service->adapter->getAllSections();

				if($sections !== false)
				{
					$container = $this->getContainer(Api_Section::OBJECT_TYPE, true);
					$status = $container->registerSet(Api_Section::FIELD_ID, $sections);

					if(!$status) {
						$container->reset();
					}

					return $status;
				}
			}

			return false;
		}

		/**
		  * @return bool
		  */
		public function refreshFolders()
		{
			if($this->isEnabled() && $this->cleaner(Api_Folder::OBJECT_TYPE))
			{
				$folders = $this->service->adapter->getAllFolders();

				if($folders !== false)
				{
					$container = $this->getContainer(Api_Folder::OBJECT_TYPE, true);
					$status = $container->registerSet(Api_Folder::FIELD_ID, $folders);

					if(!$status) {
						$container->reset();
					}

					return $status;
				}
			}

			return false;
		}

		/**
		  * @return bool
		  */
		public function refreshSubnets()
		{
			if($this->isEnabled() && $this->cleaner(Api_Subnet::OBJECT_TYPE))
			{
				$subnets = $this->service->adapter->getAllSubnets();

				if($subnets !== false)
				{
					$container = $this->getContainer(Api_Subnet::OBJECT_TYPE, true);
					$status = $container->registerSet(Api_Subnet::FIELD_ID, $subnets);

					if(!$status) {
						$container->reset();
					}

					return $status;
				}
			}

			return false;
		}

		/**
		  * @return bool
		  */
		public function refreshVlans()
		{
			if($this->isEnabled() && $this->cleaner(Api_Vlan::OBJECT_TYPE))
			{
				$vlans = $this->service->adapter->getAllVlans();

				if($vlans !== false)
				{
					$container = $this->getContainer(Api_Vlan::OBJECT_TYPE, true);
					$status = $container->registerSet(Api_Vlan::FIELD_ID, $vlans);

					if(!$status) {
						$container->reset();
					}

					return $status;
				}
			}

			return false;
		}

		/**
		  * @return bool
		  */
		public function refreshAddresses()
		{
			return false;
		}
	}