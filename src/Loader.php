<?php

namespace Hubleto\App\External\WaiBlue\VehicleLog;

class Loader extends \Hubleto\Erp\App
{
  public function init(): void
  {
    parent::init();

    $this->router()->get([
      '/^vehicle-log\/?$/' => Controllers\Trips::class,
      '/^vehicle-log\/add\/?$/' => ['controller' => Controllers\Trips::class, 'vars' => ['recordId' => -1]],
      '/^vehicle-log(\/(?<recordId>\d+))?\/?$/' => Controllers\Trips::class,
      '/^vehicle-log\/vehicles\/?$/' => Controllers\Vehicles::class,
      '/^vehicle-log\/vehicles\/add\/?$/' => ['controller' => Controllers\Vehicles::class, 'vars' => ['recordId' => -1]],
      '/^vehicle-log\/vehicles(\/(?<recordId>\d+))?\/?$/' => Controllers\Vehicles::class,
      '/^vehicle-log\/api\/reverse-geocode\/?$/' => Controllers\Api\ReverseGeocode::class,
    ]);

    $appMenu = $this->getService(\Hubleto\App\Community\Desktop\AppMenuManager::class);
    $appMenu->addItem($this, 'vehicle-log', $this->translate('Trips'), 'fas fa-route');
    $appMenu->addItem($this, 'vehicle-log/vehicles', $this->translate('Vehicles'), 'fas fa-car');
  }

  public function installApp(int $round): void
  {
    if ($round == 1) {
      $this->getModel(Models\Vehicle::class)->upgradeSchema();
      $this->getModel(Models\Trip::class)->upgradeSchema();
    }
  }

  public function renderFrontendAssets(): string
  {
    $css = (string) file_get_contents($this->srcFolder . '/Assets/vehicle-log.css');
    $js = (string) file_get_contents($this->srcFolder . '/Assets/vehicle-log.js');

    return '<style id="vehicle-log-styles">' . $css . '</style>'
      . '<script id="vehicle-log-script">' . $js . '</script>';
  }
}
