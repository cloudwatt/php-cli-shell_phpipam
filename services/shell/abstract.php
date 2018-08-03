<?php
	abstract class Service_Shell_Abstract
	{
		protected $_MAIN;

		protected $_pathIds;
		protected $_pathApi;

		protected $_searchfromCurrentPath = true;


		public function __construct(Service_Abstract $MAIN)
		{
			$this->_MAIN = $MAIN;
		}

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

		// @todo optimiser garder en cache en fct path
		abstract protected function _getObjects($path = null);

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

		public function printObjectsList($path = null)
		{
			$objects = $this->_getObjects($path);
			$this->_MAIN->deleteWaitingMsg();
			$objects = $this->_printObjectsList($objects);
			$this->_MAIN->setLastCmdResult($objects);
			return true;
		}

		protected function _printObjectsList(array $objects)
		{
			foreach($objects as $type => &$list)
			{
				if(count($list) > 0)
				{
					$this->_MAIN->e(PHP_EOL.$this->_LIST_TITLES[$type], 'black', 'white', 'bold');

					foreach($list as &$fields) {
						$fields = array_intersect_key($fields, array_flip($this->_LIST_FIELDS[$type]['fields']));
						$fields = vsprintf($this->_LIST_FIELDS[$type]['format'], $fields);
					}

					$this->_MAIN->e(PHP_EOL.implode(PHP_EOL, $list).PHP_EOL, 'grey');
				}
			}

			return $objects;
		}

		abstract public function printObjectInfos(array $args, $fromCurrentPath = true);

		protected function _printObjectInfos(array $cases, array $args, $fromCurrentPath = true)
		{
			if(isset($args[0]))
			{
				foreach($cases as $type => $method)
				{
					$infos = $this->{$method}($args[0], $fromCurrentPath);

					if(count($infos) > 0) {
						$objectType = $type;
						break;
					}
				}

				return (isset($objectType)) ? ($this->_printInformations($objectType, $infos)) : (false);
			}
			else {
				return false;
			}
		}

		protected function _printInformations($type, $items)
		{
			$this->_MAIN->deleteWaitingMsg();

			if($items !== false && Tools::is('array&&count>0', $items))
			{
				$results = array();
				$this->_MAIN->e(PHP_EOL.'INFORMATIONS', 'black', 'white', 'bold');

				foreach($items as $item)
				{
					/**
					  * Il faut réinitialiser $infos pour chaque item
					  * Permet aussi de garder l'ordre de _PRINT_FIELDS
					  */
					$infos = array();

					foreach($this->_PRINT_FIELDS[$type] as $key => $format)
					{
						if(array_key_exists($key, $item))
						{
							$field = $item[$key];

							if(Tools::is('array', $field)) {
								$field = implode(PHP_EOL, $field);
							}

							$field = vsprintf($format, $field);

							switch($key)
							{
								case 'header':
									$field = $this->_MAIN->e($field, 'green', false, 'bold', true);
									break;
							}

							$infos[] = $field;
						}
					}

					if(count($infos) > 0) {
						$results[] = $infos;
						$this->_MAIN->e(PHP_EOL.PHP_EOL.implode(PHP_EOL, $infos), 'grey');
					}
				}

				$this->_MAIN->setLastCmdResult($results);
				return true;
			}

			return false;
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

		protected function _arrayFilter(array $items, array $fields)
		{
			$results = array();

			foreach($items as $item)
			{
				if(count($item) > 0)
				{
					$result = array();

					foreach($fields as $field)
					{
						if(array_key_exists($field, $item)) {
							$result[$field] = $item[$field];
						}
					}

					if(count($result) > 0) {
						$results[] = $result;
					}
				}
			}

			return $results;
		}
	}