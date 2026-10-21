<?php

namespace Hubleto\App\External\WaiBlue\VehicleLog\Models;

use Hubleto\App\Community\Settings\Models\Company;
use Hubleto\Framework\Db\Column\Boolean;
use Hubleto\Framework\Db\Column\Decimal;
use Hubleto\Framework\Db\Column\Integer;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Varchar;

class Vehicle extends \Hubleto\Erp\Model
{
  public const PROPULSION_PETROL = 1;
  public const PROPULSION_DIESEL = 2;
  public const PROPULSION_LPG = 3;
  public const PROPULSION_CNG = 4;
  public const PROPULSION_ELECTRIC = 5;
  public const PROPULSION_PLUGIN_HYBRID = 6;
  public const PROPULSION_HYBRID = 7;
  public const PROPULSION_OTHER = 8;

  public const PROPULSION_TYPES = [
    self::PROPULSION_PETROL => 'Petrol',
    self::PROPULSION_DIESEL => 'Diesel',
    self::PROPULSION_LPG => 'LPG',
    self::PROPULSION_CNG => 'CNG',
    self::PROPULSION_ELECTRIC => 'Electric',
    self::PROPULSION_PLUGIN_HYBRID => 'Plug-in hybrid',
    self::PROPULSION_HYBRID => 'Hybrid',
    self::PROPULSION_OTHER => 'Other',
  ];

  public string $table = 'vehicle_log_vehicles';
  public string $recordManagerClass = RecordManagers\Vehicle::class;
  public ?string $lookupSqlValue = "trim(concat(ifnull({%TABLE%}.registration_number, ''), ' ', ifnull({%TABLE%}.make, ''), ' ', ifnull({%TABLE%}.model_name, '')))";
  public ?string $lookupUrlAdd = 'vehicle-log/vehicles/add';
  public ?string $lookupUrlDetail = 'vehicle-log/vehicles/{%ID%}';

  public array $relations = [
    'COMPANY' => [self::BELONGS_TO, Company::class, 'id_company', 'id'],
  ];

  public function indexes(array $indexes = []): array
  {
    return parent::indexes([
      'registration_number' => [
        'type' => 'unique',
        'columns' => ['registration_number' => ['order' => 'asc']],
      ],
      'vin' => [
        'type' => 'unique',
        'columns' => ['vin' => ['order' => 'asc']],
      ],
    ]);
  }

  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'id_company' => (new Lookup($this, $this->translate('Company'), Company::class))->setDefaultVisible(),
      'registration_number' => (new Varchar($this, $this->translate('Registration number')))->setDefaultVisible(),
      'vin' => (new Varchar($this, $this->translate('VIN')))->setDefaultVisible(),
      'make' => (new Varchar($this, $this->translate('Make')))->setDefaultVisible(),
      'model_name' => (new Varchar($this, $this->translate('Model')))->setDefaultVisible(),
      'propulsion_type' => (new Integer($this, $this->translate('Propulsion')))
        ->setEnumValues(array_map(fn(string $value): string => $this->translate($value), self::PROPULSION_TYPES))
        ->setDefaultVisible(),
      'fuel_consumption_l_per_100km' => (new Decimal($this, $this->translate('Fuel consumption')))
        ->setDecimals(2)->setStep(0.01)->setUnit('l/100 km')->setDefaultVisible(),
      'electric_consumption_kwh_per_100km' => (new Decimal($this, $this->translate('Electric consumption')))
        ->setDecimals(2)->setStep(0.01)->setUnit('kWh/100 km')->setDefaultVisible(),
      'initial_odometer_km' => (new Decimal($this, $this->translate('Initial odometer')))
        ->setDecimals(1)->setStep(0.1)->setUnit('km'),
      'initial_fuel_level_l' => (new Decimal($this, $this->translate('Initial fuel level')))
        ->setDecimals(2)->setStep(0.01)->setUnit('l'),
      'initial_battery_level_kwh' => (new Decimal($this, $this->translate('Initial battery level')))
        ->setDecimals(2)->setStep(0.01)->setUnit('kWh'),
      'is_active' => (new Boolean($this, $this->translate('Active')))->setDefaultValue(true)->setDefaultVisible(),
    ]);
  }

  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();
    $description->ui['title'] = '';
    $description->ui['addButtonText'] = $this->translate('Add vehicle');
    $description->show(['header', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);
    $description->showOnlyColumns([
      'id_company',
      'registration_number',
      'vin',
      'make',
      'model_name',
      'propulsion_type',
      'fuel_consumption_l_per_100km',
      'electric_consumption_kwh_per_100km',
      'is_active',
    ]);

    return $description;
  }

  public function describeForm(): \Hubleto\Framework\Description\Form
  {
    $description = parent::describeForm();
    $description->ui['title'] = $this->translate('Vehicle');
    $description->ui['subTitle'] = $this->translate('Vehicle log');
    $description->hide(['copyButton']);

    $inputs = $description->inputs;
    $description->inputs = [];

    foreach ([
      'id_company',
      'registration_number',
      'vin',
      'make',
      'model_name',
      'propulsion_type',
      'fuel_consumption_l_per_100km',
      'electric_consumption_kwh_per_100km',
      'initial_odometer_km',
      'initial_fuel_level_l',
      'initial_battery_level_kwh',
      'is_active',
    ] as $field) {
      if (isset($inputs[$field])) {
        $description->inputs[$field] = $inputs[$field];
      }
    }

    return $description;
  }
}
