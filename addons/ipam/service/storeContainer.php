<?php
	namespace Addon\Ipam;

	use Core as C;

	class Service_StoreContainer extends C\Addon\Service_StoreContainer
	{
		protected function _new($id)
		{
			switch($this->_type)
			{
				case Api_Section::OBJECT_TYPE: {
					return new Api_Section($id, $this->_service);
				}
				case Api_Folder::OBJECT_TYPE: {
					return new Api_Folder($id, $this->_service);
				}
				case Api_Subnet::OBJECT_TYPE: {
					return new Api_Subnet($id, $this->_service);
				}
				case Api_Vlan::OBJECT_TYPE: {
					return new Api_Vlan($id, $this->_service);
				}
				case Api_Address::OBJECT_TYPE: {
					return new Api_Address($id, $this->_service);
				}
				default: {
					return false;
				}
			}
		}
	}