<?php

namespace Hubleto\App\External\WaiBlue\VehicleLog\Models;

use Hubleto\App\Community\Auth\Models\User;
use Hubleto\Framework\Db\Column\Date;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\Framework\Db\Column\Time;
use Hubleto\Framework\Db\Column\Varchar;
use Hubleto\Framework\Exceptions\RecordSaveException;

/**
 * A planned use of a vehicle: who has it from when to when. Trips record what was driven; bookings plan it ahead.
 */
class Booking extends \Hubleto\Erp\Model
{
  public string $table = 'vehicle_log_bookings';
  public string $recordManagerClass = RecordManagers\Booking::class;
  public ?string $lookupSqlValue = "concat(ifnull({%TABLE%}.date_start, ''), ' ', ifnull({%TABLE%}.name, ''))";
  public ?string $lookupUrlDetail = 'vehicle-log/bookings/{%ID%}';

  public array $relations = [
    'VEHICLE' => [self::BELONGS_TO, Vehicle::class, 'id_vehicle', 'id'],
    'DRIVER' => [self::BELONGS_TO, User::class, 'id_driver', 'id'],
  ];

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'id_vehicle' => (new Lookup($this, $this->translate('Vehicle'), Vehicle::class, 'RESTRICT'))
        ->setRequired()
        ->setDefaultVisible()
      ,
      'name' => (new Varchar($this, $this->translate('Name')))
        ->setDefaultVisible()
        ->setHint($this->translate('Left empty, it is the vehicle.'))
      ,
      'date_start' => (new Date($this, $this->translate('Start date')))->setRequired()->setDefaultVisible(),
      'time_start' => (new Time($this, $this->translate('Start time')))->setDefaultVisible(),
      'date_end' => (new Date($this, $this->translate('End date')))->setRequired()->setDefaultVisible(),
      'time_end' => (new Time($this, $this->translate('End time')))->setDefaultVisible(),
      'id_driver' => (new Lookup($this, $this->translate('Driver'), User::class))
        ->setReactComponent('InputUserSelect')
        ->setDefaultValue($this->authProvider()->getUserId())
        ->setDefaultVisible()
      ,
      'note' => (new Text($this, $this->translate('Note')))->setDefaultVisible(),
    ]);
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();
    $description->ui['title'] = '';
    $description->ui['addButtonText'] = $this->translate('Book a vehicle');
    $description->setOrderBy('date_start', 'asc');
    $description->show(['header', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);
    $description->hide(['footer']);

    // In the bookings tab of a vehicle the vehicle is known, so its column is left out.
    $idVehicle = $this->router()->urlParamAsInteger('idVehicle');
    if ($idVehicle > 0) {
      $description->hideColumns(['id_vehicle']);
      $description->ui['addButtonText'] = $this->translate('Book the vehicle');
    }

    return $description;
  }

  public function describeForm(): \Hubleto\Framework\Description\Form
  {
    $description = parent::describeForm();
    $description->ui['title'] = $this->translate('Booking');
    $description->ui['subTitle'] = $this->translate('Vehicle log');
    $description->hide(['copyButton']);
    return $description;
  }

  public function onBeforeCreate(array $record): array
  {
    $record = parent::onBeforeCreate($record);
    $record = $this->fillNameFromVehicle($record);
    $this->refuseEndBeforeStart($record);
    $this->refuseOverlappingBooking($record);
    return $record;
  }

  public function onBeforeUpdate(array $record): array
  {
    $record = parent::onBeforeUpdate($record);
    $record = $this->addStoredValues($record);
    $record = $this->fillNameFromVehicle($record);
    $this->refuseEndBeforeStart($record);
    $this->refuseOverlappingBooking($record);
    return $record;
  }

  // A save may send only some columns; the checks need the vehicle, dates and times, so the stored ones are used.
  private function addStoredValues(array $record): array
  {
    $storedBooking = $this->record->where('id', (int) ($record['id'] ?? 0))->first();
    if (!$storedBooking) return $record;

    foreach (['id_vehicle', 'date_start', 'time_start', 'date_end', 'time_end'] as $column) {
      if (!array_key_exists($column, $record)) $record[$column] = $storedBooking->$column;
    }
    return $record;
  }

  // A booking without a name is named after its vehicle, so the calendar shows which vehicle is taken.
  private function fillNameFromVehicle(array $record): array
  {
    $hasName = trim((string) ($record['name'] ?? '')) != '';
    if ($hasName) return $record;

    $vehicle = $this->getModel(Vehicle::class)->record->where('id', (int) ($record['id_vehicle'] ?? 0))->first();
    if (!$vehicle) return $record;

    $vehicleName = trim(($vehicle->registration_number ?? '') . ' ' . ($vehicle->make ?? '') . ' ' . ($vehicle->model_name ?? ''));
    // As the vehicle's dropdown text: a vehicle without these is "#<id>".
    if ($vehicleName == '') $vehicleName = '#' . $vehicle->id;
    $record['name'] = $vehicleName;
    return $record;
  }

  // Start of the booking; without a time it starts at the beginning of the day (as in the calendar).
  private function getStart(array $record): string
  {
    $timeStart = (string) ($record['time_start'] ?? '');
    return $record['date_start'] . ' ' . ($timeStart == '' ? '00:00:00' : $timeStart);
  }

  // End of the booking; without a time it ends at the end of the day (as in the calendar).
  private function getEnd(array $record): string
  {
    $timeEnd = (string) ($record['time_end'] ?? '');
    return $record['date_end'] . ' ' . ($timeEnd == '' ? '23:59:59' : $timeEnd);
  }

  private function hasBothDates(array $record): bool
  {
    return !empty($record['date_start']) && !empty($record['date_end']);
  }

  private function refuseEndBeforeStart(array $record): void
  {
    if (!$this->hasBothDates($record)) return;

    if (strtotime($this->getEnd($record)) < strtotime($this->getStart($record))) {
      throw new RecordSaveException($this->translate('The booking cannot end before it starts.'), [[
        'name' => 'Booking.date_end',
        'id' => (int) ($record['id'] ?? -1),
      ]]);
    }
  }

  // A vehicle can be booked only once at a time. A booking may start at the moment another one ends.
  private function refuseOverlappingBooking(array $record): void
  {
    $idVehicle = (int) ($record['id_vehicle'] ?? 0);
    if ($idVehicle <= 0 || !$this->hasBothDates($record)) return;

    $storedStartSql = "timestamp({$this->table}.date_start, ifnull({$this->table}.time_start, '00:00:00'))";
    $storedEndSql = "timestamp({$this->table}.date_end, ifnull({$this->table}.time_end, '23:59:59'))";

    $overlappingBooking = $this->record
      ->where($this->table . '.id_vehicle', $idVehicle)
      ->where($this->table . '.id', '<>', (int) ($record['id'] ?? 0))
      ->whereRaw($storedStartSql . ' < ?', [$this->getEnd($record)])
      ->whereRaw($storedEndSql . ' > ?', [$this->getStart($record)])
      ->with('DRIVER')
      ->first()
    ;
    if (!$overlappingBooking) return;

    $driverName = '';
    if ($overlappingBooking->DRIVER) {
      $driverName = trim($overlappingBooking->DRIVER->first_name . ' ' . $overlappingBooking->DRIVER->last_name);
      if ($driverName == '') $driverName = (string) $overlappingBooking->DRIVER->email;
    }

    $overlappingRecord = $overlappingBooking->toArray();
    $messageVars = [
      'start' => date('Y-m-d H:i', strtotime($this->getStart($overlappingRecord))),
      'end' => date('Y-m-d H:i', strtotime($this->getEnd($overlappingRecord))),
      'driver' => $driverName,
    ];
    if ($driverName == '') {
      $message = $this->translate('The vehicle is already booked from {{ start }} to {{ end }}.', $messageVars);
    } else {
      $message = $this->translate('The vehicle is already booked from {{ start }} to {{ end }} ({{ driver }}).', $messageVars);
    }
    throw new RecordSaveException($message, [[
      'name' => 'Booking.date_start',
      'id' => (int) ($record['id'] ?? -1),
    ]]);
  }
}
