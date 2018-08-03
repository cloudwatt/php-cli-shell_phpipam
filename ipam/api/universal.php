<?php
	class Ipam_Api_Universal
	{
		protected static $_IPAM = null;


		public function getNmi($hostName, $gateway = true)
		{
			$IPAM = self::$_IPAM->getIpam($hostName);

			$vlanName = '[-_](nmi)$';	// /!\ Arg 2 doit être passé par référence
			$mgmtDatas = $IPAM->getByEquipLabelVlanName($hostName, $vlanName, 4);

			if($gateway === true)
			{
				$mgmtDatas['gateway'] = $IPAM->getGatewayBySubnetId($mgmtDatas);

				if($mgmtDatas['gateway'] === false) {
					throw new Exception("Impossible de récupérer l'IP de la Gateway depuis l'IPAM pour \"".$hostName."\"", E_USER_ERROR);
				}

				unset($mgmtDatas['subnetId']);
			}

			return $mgmtDatas;
		}

		public function __call($name, $arguments)
		{
			return call_user_func_array(array(self::$_IPAM, $name), $arguments);
		}

		public static function __callStatic($name, $arguments)
		{
			return forward_static_call_array(array(get_class(self::$_IPAM), $name), $arguments);
		}

		public static function setIpam(IPAM $IPAM)
		{
			self::$_IPAM = $IPAM;
		}
	}