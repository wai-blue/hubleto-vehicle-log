<?php

namespace Hubleto\App\External\WaiBlue\VehicleLog\Models;

use Hubleto\App\Community\Auth\Models\User;
use Hubleto\App\External\WaiBlue\VehicleLog\Services\TripCalculationException;
use Hubleto\App\External\WaiBlue\VehicleLog\Services\TripCalculator;
use Hubleto\Framework\Db\Column\Date;
use Hubleto\Framework\Db\Column\DateTime;
use Hubleto\Framework\Db\Column\Decimal;
use Hubleto\Framework\Db\Column\Integer;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\Framework\Db\Column\Time;
use Hubleto\Framework\Db\Column\Varchar;
use Hubleto\Framework\Exceptions\RecordSaveException;

class Trip extends \Hubleto\Erp\Model
{
  public string $table = 'vehicle_log_trips';
  public string $recordManagerClass = RecordManagers\Trip::class;
  public ?string $lookupSqlValue = "concat(ifnull({%TABLE%}.trip_date, ''), ' ', ifnull({%TABLE%}.route_from, ''), ' - ', ifnull({%TABLE%}.route_to, ''))";
  public ?string $lookupUrlDetail = 'vehicle-log/{%ID%}';

  public array $relations = [
    'VEHICLE' => [self::BELONGS_TO, Vehicle::class, 'id_vehicle', 'id'],
    'DRIVER' => [self::BELONGS_TO, User::class, 'id_driver', 'id'],
  ];

  public function indexes(array $indexes = []): array
  {
    return parent::indexes([
      'vehicle_sequence' => [
        'type' => 'unique',
        'columns' => [
          'id_vehicle' => ['order' => 'asc'],
          'sequence_number' => ['order' => 'asc'],
        ],
      ],
    ]);
  }

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'sequence_number' => (new Integer($this, $this->translate('No.')))->setReadonly()->setDefaultVisible(),
      'trip_date' => (new Date($this, $this->translate('Date')))->setDefaultValue(date('Y-m-d'))->setDefaultVisible(),
      'id_vehicle' => (new Lookup($this, $this->translate('Vehicle'), Vehicle::class))->setDefaultVisible(),
      'id_driver' => (new Lookup($this, $this->translate('Driver'), User::class))
        ->setReactComponent('InputUserSelect')
        ->setDefaultValue($this->authProvider()->getUserId())
        ->setDefaultVisible(),
      'time_start' => (new Time($this, $this->translate('Start time')))->setDefaultVisible(),
      'time_end' => (new Time($this, $this->translate('End time')))->setDefaultVisible(),
      'duration_minutes' => (new Integer($this, $this->translate('Duration')))->setReadonly()->setUnit('min'),
      'route_from' => (new Varchar($this, $this->translate('Route from')))->setDefaultVisible(),
      'route_to' => (new Varchar($this, $this->translate('Route to')))->setDefaultVisible(),
      'start_latitude' => (new Decimal($this, $this->translate('Start latitude')))->setDecimals(7)->setStep(0.0000001),
      'start_longitude' => (new Decimal($this, $this->translate('Start longitude')))->setDecimals(7)->setStep(0.0000001),
      'end_latitude' => (new Decimal($this, $this->translate('End latitude')))->setDecimals(7)->setStep(0.0000001),
      'end_longitude' => (new Decimal($this, $this->translate('End longitude')))->setDecimals(7)->setStep(0.0000001),
      'purpose' => (new Text($this, $this->translate('Purpose')))->setDefaultVisible(),
      'odometer_start_km' => (new Decimal($this, $this->translate('Odometer at start')))->setDecimals(1)->setStep(0.1)->setUnit('km'),
      'odometer_end_km' => (new Decimal($this, $this->translate('Odometer at end')))->setDecimals(1)->setStep(0.1)->setUnit('km')->setDefaultVisible(),
      'distance_km' => (new Decimal($this, $this->translate('Distance')))->setDecimals(1)->setStep(0.1)->setUnit('km')->setReadonly()->setDefaultVisible(),
      'business_km' => (new Decimal($this, $this->translate('Business km')))->setDecimals(1)->setStep(0.1)->setUnit('km')->setDefaultValue(0),
      'private_km' => (new Decimal($this, $this->translate('Private km')))->setDecimals(1)->setStep(0.1)->setUnit('km')->setDefaultValue(0),
      'fuel_purchased_l' => (new Decimal($this, $this->translate('Fuel purchased')))->setDecimals(2)->setStep(0.01)->setUnit('l')->setDefaultValue(0),
      'electricity_purchased_kwh' => (new Decimal($this, $this->translate('Electricity purchased')))->setDecimals(2)->setStep(0.01)->setUnit('kWh')->setDefaultValue(0),
      'purchase_price_incl_vat' => (new Decimal($this, $this->translate('Purchase price incl. VAT')))->setDecimals(2)->setStep(0.01)->setUnit('EUR')->setDefaultValue(0),
      'expected_fuel_consumption_l' => (new Decimal($this, $this->translate('Expected fuel consumption')))->setDecimals(2)->setUnit('l')->setReadonly(),
      'expected_electricity_consumption_kwh' => (new Decimal($this, $this->translate('Expected electricity consumption')))->setDecimals(2)->setUnit('kWh')->setReadonly(),
      'notes' => (new Text($this, $this->translate('Notes'))),
      'datetime_created' => (new DateTime($this, $this->translate('Created')))->setDefaultValue(date('Y-m-d H:i:s'))->setReadonly()->setRequired(),
    ]);
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();
    $description->ui['title'] = '';
    $description->ui['addButtonText'] = $this->translate('Add trip');
    $description->setOrderBy('trip_date', 'desc');
    $description->show(['header', 'footer', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);
    $description->showOnlyColumns([
      'sequence_number',
      'trip_date',
      'id_vehicle',
      'id_driver',
      'time_start',
      'time_end',
      'route_from',
      'route_to',
      'purpose',
      'business_km',
      'private_km',
      'distance_km',
      'odometer_end_km',
      'fuel_purchased_l',
      'electricity_purchased_kwh',
      'purchase_price_incl_vat',
    ]);

    $description->addFilter('fPeriod', [
      'title' => $this->translate('Period'),
      'options' => [
        'all' => $this->translate('All'),
        'today' => $this->translate('Today'),
        'thisMonth' => $this->translate('This month'),
        'lastMonth' => $this->translate('Last month'),
        'thisYear' => $this->translate('This year'),
      ],
      'default' => 'all',
    ]);

    $vehicleOptions = [];
    foreach ($this->vehicleModel()->record->prepareReadQuery()->orderBy('registration_number')->get() as $vehicle) {
      $label = trim(($vehicle->registration_number ?? '') . ' ' . ($vehicle->make ?? '') . ' ' . ($vehicle->model_name ?? ''));
      $vehicleOptions[(int) $vehicle->id] = $label !== '' ? $label : '#' . $vehicle->id;
    }
    $description->addFilter('fVehicle', [
      'title' => $this->translate('Vehicle'),
      'type' => 'multipleSelectButtons',
      'options' => $vehicleOptions,
    ]);

    return $description;
  }

  public function describeForm(): \Hubleto\Framework\Description\Form
  {
    $description = parent::describeForm();
    $description->ui['title'] = $this->translate('Trip');
    $description->ui['subTitle'] = $this->translate('Vehicle log');
    $description->hide(['copyButton']);

    $inputs = $description->inputs;
    $description->inputs = [];

    foreach ([
      'trip_date',
      'id_vehicle',
      'id_driver',
      'time_start',
      'time_end',
      'duration_minutes',
      'route_from',
      'route_to',
      'purpose',
      'odometer_start_km',
      'odometer_end_km',
      'distance_km',
      'business_km',
      'private_km',
      'fuel_purchased_l',
      'electricity_purchased_kwh',
      'purchase_price_incl_vat',
      'expected_fuel_consumption_l',
      'expected_electricity_consumption_kwh',
      'notes',
    ] as $field) {
      if (isset($inputs[$field])) {
        $description->inputs[$field] = $inputs[$field];
      }
    }

    return $description;
  }

  public function onBeforeCreate(array $record): array
  {
    return parent::onBeforeCreate($this->calculate($record, true));
  }

  public function onBeforeUpdate(array $record): array
  {
    return parent::onBeforeUpdate($this->calculate($record, false));
  }

  private function calculate(array $record, bool $creating): array
  {
    $vehicle = null;
    $idVehicle = (int) ($record['id_vehicle'] ?? 0);
    if ($idVehicle > 0) {
      $vehicleRecord = $this->vehicleModel()->record->prepareReadQuery()->where('id', $idVehicle)->first();
      $vehicle = $vehicleRecord?->toArray();
    }

    $nextSequence = 0;
    if ($creating && $idVehicle > 0) {
      $nextSequence = ((int) $this->records()->prepareReadQuery()->where('id_vehicle', $idVehicle)->max('sequence_number')) + 1;
    }

    try {
      return TripCalculator::calculate($record, $vehicle, $nextSequence);
    } catch (TripCalculationException $e) {
      throw new RecordSaveException($e->getMessage(), [[
        'name' => 'Trip.' . $e->field,
        'id' => (int) ($record['id'] ?? -1),
      ]], $e);
    }
  }

  private function vehicleModel(): Vehicle
  {
    return $this->getService(Vehicle::class);
  }

  private function records(): RecordManagers\Trip
  {
    if (!$this->record instanceof RecordManagers\Trip) {
      throw new \RuntimeException('Vehicle log trip record manager is not initialized.');
    }

    return $this->record;
  }
}
