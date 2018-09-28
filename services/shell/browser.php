<?php
	require_once(__DIR__ . '/abstract.php');

	abstract class Service_Shell_Abstract_Browser extends Service_Shell_Abstract
	{
		protected $_pathIds;
		protected $_pathApi;


		public function updatePath(array $pathIds, array $pathApi)
		{
			$this->_pathIds = $pathIds;
			$this->_pathApi = $pathApi;
			return $this;
		}

		protected function _browser($path = null, $returnCurrentApi = true)
		{
			$pathIds = $this->_pathIds;
			$pathApi = $this->_pathApi;

			if($path !== null) {
				// /!\ browser modifie pathIds et pathApi, passage par référence
				$this->_MAIN->browser($pathIds, $pathApi, $path);
			}

			return ($returnCurrentApi) ? (end($pathApi)) : ($pathApi);
		}

		public function getOptions($path = null)
		{
			$options = array();
			$objects = $this->_getObjects($path);

			foreach($objects as $type => $list)
			{
				if(count($list) > 0)
				{
					foreach($list as $fields)
					{
						if(array_key_exists($type, $this->_OPTION_FIELDS)) {
							$optFields = array_flip($this->_OPTION_FIELDS[$type]['fields']);
							$option = array_intersect_key($fields, $optFields);
							$options = array_merge($options, array_values($option));
						}
					}
				}
			}

			return $options;
		}

		protected function _getLastApiPath(array $pathApi, $apiClassName)
		{
			$pathApi = array_reverse($pathApi);

			foreach($pathApi as $api)
			{
				if(get_class($api) === $apiClassName) {
					return $api;
				}
			}

			return false;
		}
	}