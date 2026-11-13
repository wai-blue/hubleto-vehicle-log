<?php

namespace Hubleto\App\External\WaiBlue\VehicleLog\Extendibles;

/**
 * Items of the app's menu in the top bar (Hubleto's Desktop reads the AppMenu extendible of the active app).
 */
class AppMenu extends \Hubleto\Framework\Extendible
{
  public function getItems(): array
  {
    return [
      [
        'app' => $this->app,
        'url' => 'vehicle-log',
        'title' => $this->app->translate('Trips'),
        'icon' => 'fas fa-route',
      ],
      [
        'app' => $this->app,
        'url' => 'vehicle-log/vehicles',
        'title' => $this->app->translate('Vehicles'),
        'icon' => 'fas fa-car',
      ],
      [
        'app' => $this->app,
        'url' => 'vehicle-log/bookings',
        'title' => $this->app->translate('Bookings'),
        'icon' => 'fas fa-calendar-check',
      ],
    ];
  }
}
