<?php

namespace Hubleto\App\External\WaiBlue\VehicleLog\Models\Migrations;

use Hubleto\Framework\Migration;

class Vehicle_0001 extends Migration
{
  public function upgradeSchema(): void
  {
    $this->db->execute(<<<SQL
CREATE TABLE IF NOT EXISTS `vehicle_log_vehicles` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `id_company` int(8) NULL DEFAULT NULL,
  `registration_number` varchar(255) NULL DEFAULT NULL,
  `vin` varchar(255) NULL DEFAULT NULL,
  `make` varchar(255) NULL DEFAULT NULL,
  `model_name` varchar(255) NULL DEFAULT NULL,
  `propulsion_type` int(255) NULL DEFAULT NULL,
  `fuel_consumption_l_per_100km` decimal(14,2) NULL DEFAULT NULL,
  `electric_consumption_kwh_per_100km` decimal(14,2) NULL DEFAULT NULL,
  `initial_odometer_km` decimal(14,1) NULL DEFAULT NULL,
  `initial_fuel_level_l` decimal(14,2) NULL DEFAULT NULL,
  `initial_battery_level_kwh` decimal(14,2) NULL DEFAULT NULL,
  `is_active` int(1) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicle_log_vehicles_id_company_index` (`id_company`),
  UNIQUE KEY `vehicle_log_vehicles_registration_number_unique` (`registration_number`),
  UNIQUE KEY `vehicle_log_vehicles_vin_unique` (`vin`),
  CONSTRAINT `vehicle_log_vehicles_company_fk`
    FOREIGN KEY (`id_company`) REFERENCES `companies` (`id`)
    ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
  }

  public function downgradeSchema(): void
  {
    $this->db->execute('DROP TABLE IF EXISTS `vehicle_log_vehicles`;');
  }

  public function upgradeForeignKeys(): void
  {
  }

  public function downgradeForeignKeys(): void
  {
  }
}
