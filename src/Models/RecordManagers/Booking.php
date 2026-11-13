<?php

namespace Hubleto\App\External\WaiBlue\VehicleLog\Models\RecordManagers;

use Hubleto\App\Community\Auth\Models\RecordManagers\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends \Hubleto\Erp\RecordManager
{
  public $table = 'vehicle_log_bookings';

  public function VEHICLE(): BelongsTo
  {
    return $this->belongsTo(Vehicle::class, 'id_vehicle', 'id');
  }

  public function DRIVER(): BelongsTo
  {
    return $this->belongsTo(User::class, 'id_driver', 'id');
  }

  public function prepareReadQuery(mixed $query = null, int $level = 0, array|null $includeRelations = null): mixed
  {
    $query = parent::prepareReadQuery($query, $level, $includeRelations);

    // The bookings tab of a vehicle sends idVehicle.
    $hubleto = \Hubleto\Framework\Loader::getGlobalApp();
    $idVehicle = $hubleto->router()->urlParamAsInteger('idVehicle');
    if ($idVehicle > 0) {
      $query->where($this->table . '.id_vehicle', $idVehicle);
    }

    return $query;
  }
}
