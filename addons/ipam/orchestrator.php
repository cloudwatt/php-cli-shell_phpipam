<?php
	namespace Addon\Ipam;

	use Core as C;

	class Orchestrator extends C\Addon\Orchestrator
	{
		const SERVICE_NAME = 'IPAM';

		/**
		  * @var Addon\Ipam\Orchestrator
		  */
		private static $_instance;


		/**
		  * @return bool
		  */
		public static function hasInstance()
		{
			return (self::$_instance !== null);
		}

		/**
		  * @param Core\Config $config
		  * @return Addon\Dcim\Orchestrator
		  */
		public static function getInstance(C\Config $config = null)
		{
			if(self::$_instance === null) {
				self::$_instance = new self($config);
			}

			return self::$_instance;
		}

		/**
		  * @param string $id
		  * @return Addon\Dcim\Service
		  */
		protected function _newService($id)
		{
			return new Service($id, $this->_config);
		}
	}