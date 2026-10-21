<?php

namespace Hubleto\App\External\WaiBlue\VehicleLog\Models\Migrations;

use Hubleto\Framework\Migration;

class Trip_0001 extends Migration
{
  public function upgradeSchema(): void
  {
    $this->db->execute(<<<SQL
CREATE TABLE IF NOT EXISTS `vehicle_log_trips` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `sequence_number` int(255) NULL DEFAULT NULL,
  `trip_date` date NULL DEFAULT NULL,
  `id_vehicle` int(8) NULL DEFAULT NULL,
  `id_driver` int(8) NULL DEFAULT NULL,
  `time_start` time NULL DEFAULT NULL,
  `time_end` time NULL DEFAULT NULL,
  `duration_minutes` int(255) NULL DEFAULT NULL,
  `route_from` varchar(255) NULL DEFAULT NULL,
  `route_to` varchar(255) NULL DEFAULT NULL,
  `start_latitude` decimal(14,7) NULL DEFAULT NULL,
  `start_longitude` decimal(14,7) NULL DEFAULT NULL,
  `end_latitude` decimal(14,7) NULL DEFAULT NULL,
  `end_longitude` decimal(14,7) NULL DEFAULT NULL,
  `purpose` text NULL,
  `odometer_start_km` decimal(14,1) NULL DEFAULT NULL,
  `odometer_end_km` decimal(14,1) NULL DEFAULT NULL,
  `distance_km` decimal(14,1) NULL DEFAULT NULL,
  `business_km` decimal(14,1) NULL DEFAULT NULL,
  `private_km` decimal(14,1) NULL DEFAULT NULL,
  `fuel_purchased_l` decimal(14,2) NULL DEFAULT NULL,
  `electricity_purchased_kwh` decimal(14,2) NULL DEFAULT NULL,
  `purchase_price_incl_vat` decimal(14,2) NULL DEFAULT NULL,
  `expected_fuel_consumption_l` decimal(14,2) NULL DEFAULT NULL,
  `expected_electricity_consumption_kwh` decimal(14,2) NULL DEFAULT NULL,
  `notes` text NULL,
  `datetime_created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicle_log_trips_trip_date_index` (`trip_date`),
  KEY `vehicle_log_trips_id_vehicle_index` (`id_vehicle`),
  KEY `vehicle_log_trips_id_driver_index` (`id_driver`),
  UNIQUE KEY `vehicle_log_trips_vehicle_sequence_unique` (`id_vehicle`, `sequence_number`),
  CONSTRAINT `vehicle_log_trips_vehicle_fk`
    FOREIGN KEY (`id_vehicle`) REFERENCES `vehicle_log_vehicles` (`id`)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `vehicle_log_trips_driver_fk`
    FOREIGN KEY (`id_driver`) REFERENCES `users` (`id`)
    ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
  }

  public function downgradeSchema(): void
  {
    $this->db->execute('DROP TABLE IF EXISTS `vehicle_log_trips`;');
  }

  public function upgradeForeignKeys(): void
  {
  }

  public function downgradeForeignKeys(): void
  {
  }
}
