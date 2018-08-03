<?php
	interface IPAM_Interface	// /!\ Permets de garantir que l'ensemble des méthodes IPAM existent dans l'orchestrateur et le connecteur
	{
		public function getByEquipLabel($equipLabel, $IPv = 4);
		public function getByEquipLabelVlanName($equipLabel, $vlanName, $IPv = 4);
		public function getByEquipLabelPortLabel($equipLabel, $portLabel, $IPv = 4);
		public function getGatewayBySubnetId($subnetId);
		public function getVlanNamesByVlanIds(array $vlanIds, array $environments);
		public function getMcLagRowset($equipLabel, $portName, $IPv = 4);
		public function getVrrpRowset($equipLabel, $subnetId, $IPv = 4);
	}