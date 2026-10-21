<?php

namespace Hubleto\App\External\WaiBlue\VehicleLog\Models\RecordManagers;

use Hubleto\App\Community\Settings\Models\RecordManagers\Company;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends \Hubleto\Erp\RecordManager
{
  public $table = 'vehicle_log_vehicles';

  public function COMPANY(): BelongsTo
  {
    return $this->belongsTo(Company::class, 'id_company', 'id');
  }
}
