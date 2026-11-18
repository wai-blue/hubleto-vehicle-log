<?php

namespace Hubleto\App\External\WaiBlue\VehicleLog\Models\Migrations;

use Hubleto\Framework\Migration;

// Passengers of the booking (JSON list of user ids).
class Booking_0002 extends Migration
{
  public function upgradeSchema(): void
  {
    $this->db->execute("ALTER TABLE `vehicle_log_bookings` ADD COLUMN `passengers` text NULL DEFAULT NULL AFTER `id_driver`");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("ALTER TABLE `vehicle_log_bookings` DROP COLUMN `passengers`");
  }

  public function upgradeForeignKeys(): void
  {
  }

  public function downgradeForeignKeys(): void
  {
  }
}
