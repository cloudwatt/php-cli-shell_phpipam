<?php
	namespace Addon\Ipam;

	use Core as C;

	class Service_Store extends C\Addon\Service_Store
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
		  * @return Addon\Ipam\Service_StoreContainer
		  */
		protected function _newContainer($type)
		{
			return new Service_StoreContainer($this->_service, $type, false);
		}
	}