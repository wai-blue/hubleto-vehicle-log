<?php

namespace Hubleto\App\External\WaiBlue\VehicleLog\Models\Migrations;

use Hubleto\Framework\Migration;

class Booking_0001 extends Migration
{
  public function upgradeSchema(): void
  {
    $this->db->execute(<<<SQL
CREATE TABLE IF NOT EXISTS `vehicle_log_bookings` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `id_vehicle` int(8) NULL DEFAULT NULL,
  `name` varchar(255) NULL DEFAULT NULL,
  `date_start` date NULL DEFAULT NULL,
  `time_start` time NULL DEFAULT NULL,
  `date_end` date NULL DEFAULT NULL,
  `time_end` time NULL DEFAULT NULL,
  `id_driver` int(8) NULL DEFAULT NULL,
  `note` text NULL,
  PRIMARY KEY (`id`),
  KEY `vehicle_log_bookings_id_vehicle_index` (`id_vehicle`),
  KEY `vehicle_log_bookings_dates_index` (`date_start`, `date_end`),
  KEY `vehicle_log_bookings_id_driver_index` (`id_driver`),
  CONSTRAINT `vehicle_log_bookings_vehicle_fk`
    FOREIGN KEY (`id_vehicle`) REFERENCES `vehicle_log_vehicles` (`id`)
    ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `vehicle_log_bookings_driver_fk`
    FOREIGN KEY (`id_driver`) REFERENCES `users` (`id`)
    ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
  }

  public function downgradeSchema(): void
  {
    $this->db->execute('DROP TABLE IF EXISTS `vehicle_log_bookings`;');
  }

  public function upgradeForeignKeys(): void
  {
  }

  public function downgradeForeignKeys(): void
  {
  }
}
