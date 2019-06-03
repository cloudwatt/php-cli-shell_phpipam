<?php
	namespace Addon\Ipam;

	use Core as C;

	class Service extends C\Addon\Service
	{
		const SERVICE_NAME = 'IPAM';

		const URL_CONFIG_FIELD = 'serverLocation';
		const LOGIN_CONFIG_FIELD = 'loginCredential';
		const LOGIN_ENV_CONFIG_FIELD = 'loginEnvVarName';
		const PASSWORD_CONFIG_FIELD = 'passwordCredential';
		const PASSWORD_ENV_CONFIG_FIELD = 'passwordEnvVarName';


		/**
		  * @return string
		  */
		public function getMethod()
		{
			return Connector_Rest::METHOD;
		}
	
		/**
		  * @return bool
		  */
		public function hasConfig()
		{
			return isset($this->_config['servers'][$this->_id]);
		}

		/**
		  * @param string $default
		  * @return mixed|Core\Config
		  */
		protected function _getConfig($default = null)
		{
			return ($this->hasConfig()) ? ($this->_config['servers'][$this->_id]) : ($default);
		}

		protected function _newCache()
		{
			$cache = new Service_Cache($this, false);
			$cache->debug($this->_debug);
			return $cache;
		}

		protected function _newStore()
		{
			$store = new Service_Store($this, true);
			$store->debug($this->_debug);
			return $store;
		}

		protected function _initAdapter()
		{
			if(($config = $this->_getConfig(false)) !== false) {
				$this->_adapter = $this->_newAdapter($config);
			}
			else {
				throw new Exception("Unable to retrieve ".static::SERVICE_NAME." service '".$this->_id."' configuration", E_USER_ERROR);
			}
		}

		protected function _newAdapter(C\Config $config = null)
		{
			$serverUrl = $this->_getUrl($config, $this->_id);
			$serverApp = $this->_getApp($this->_config, $this->_id);
			$credentials = $this->_getCredentials($config, $this->_id);
			list($loginCredential, $passwordCredential) = $credentials;

			return new Connector_Rest($this, $this->_config, $serverUrl, $serverApp, $loginCredential, $passwordCredential, $this->_debug);
		}

		protected function _getApp(C\Config $serviceConfig, $id)
		{
			if(isset($serviceConfig['contexts'][$id])) {
				return $serviceConfig['contexts'][$id];
			}
			else {
				throw new Exception("Unable to retrieve 'contexts' configuration for ".static::SERVICE_NAME." service '".$id."'", E_USER_ERROR);
			}
		}
	}