<?php

namespace Hubleto\App\External\WaiBlue\VehicleLog\Models\RecordManagers;

use Hubleto\App\Community\Auth\Models\RecordManagers\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trip extends \Hubleto\Erp\RecordManager
{
  public $table = 'vehicle_log_trips';

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
    $hubleto = \Hubleto\Framework\Loader::getGlobalApp();
    $filters = $hubleto->router()->urlParamAsArray('filters');

    switch ($filters['fPeriod'] ?? 'all') {
      case 'today':
        $query->whereDate($this->table . '.trip_date', date('Y-m-d'));
        break;
      case 'thisMonth':
        $query->whereMonth($this->table . '.trip_date', date('m'))->whereYear($this->table . '.trip_date', date('Y'));
        break;
      case 'lastMonth':
        $lastMonth = strtotime('-1 month');
        $query->whereMonth($this->table . '.trip_date', date('m', $lastMonth))->whereYear($this->table . '.trip_date', date('Y', $lastMonth));
        break;
      case 'thisYear':
        $query->whereYear($this->table . '.trip_date', date('Y'));
        break;
    }

    if (!empty($filters['fVehicle'])) {
      $query->whereIn($this->table . '.id_vehicle', array_map('intval', (array) $filters['fVehicle']));
    }

    return $query;
  }
}
