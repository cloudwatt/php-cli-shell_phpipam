<?php
	namespace Addon\Ipam;

	interface Connector_Interface
	{
		public function getByEquipLabelPortPresents($equipLabel, $portPresents = true, $IPv = 4, $strict = false);
		public function getByEquipLabelPortLabel($equipLabel, $portLabel, $IPv = 4, $strict = false);
		public function getByEquipLabelVlanId($equipLabel, $vlanId, $IPv = 4);
		public function getByEquipLabelVlanName($equipLabel, $vlanName, $IPv = 4);
		public function getByEquipLabelVlanRegex($equipLabel, $vlanRegex, $IPv = 4);
		public function getGatewayBySubnetId($subnetId);
		public function getVlanNamesByVlanIds(array $vlanIds, array $environments);
		public function getMcLagRowset($equipLabel, $portName, $IPv = 4);
		public function getVrrpRowset($equipLabel, $subnetId, $IPv = 4);
	}