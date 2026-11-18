<?php

namespace Hubleto\App\External\WaiBlue\VehicleLog\Models\RecordManagers;

use Hubleto\App\Community\Auth\Models\RecordManagers\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends \Hubleto\Erp\RecordManager
{
  public $table = 'vehicle_log_bookings';

  public function recordCreate(array $record, $useProvidedRecordId = false): array
  {
    return $this->withLockedVehicles($record, fn(): array => parent::recordCreate($record, $useProvidedRecordId));
  }

  public function recordUpdate(array $record, array $originalRecord = []): array
  {
    if ((int) ($record['id'] ?? 0) <= 0) return [];
    return $this->withLockedVehicles($record, fn(): array => parent::recordUpdate($record, $originalRecord));
  }

  private function withLockedVehicles(array $record, \Closure $save): array
  {
    $connection = $this->getConnection();

    return $connection->transaction(function () use ($record, $save, $connection): array {
      $stored = null;
      if ((int) ($record['id'] ?? 0) > 0) {
        $stored = $this->newQuery()->whereKey((int) $record['id'])->lockForUpdate()->first();
      }

      $vehicleIds = array_unique(array_filter([
        (int) ($stored?->id_vehicle ?? 0),
        (int) ($record['id_vehicle'] ?? $stored?->id_vehicle ?? 0),
      ], fn(int $id): bool => $id > 0));

      $connection->table((new Vehicle())->getTable())
        ->whereIn('id', $vehicleIds)->orderBy('id')->lockForUpdate()->get();

      return $save();
    }, 3);
  }

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

  // Bookings the user drives or rides in.
  public function whereUserDrivesOrRides(mixed $query, int $idUser): mixed
  {
    return $query->where(function ($bookingsOfUser) use ($idUser) {
      $bookingsOfUser->where($this->table . '.id_driver', $idUser);
      $bookingsOfUser->orWhere(function ($bookingsWithUserAsPassenger) use ($idUser) {
        $this->whereUserIdListContains($bookingsWithUserAsPassenger, 'passengers', $idUser);
      });
    });
  }
}
