<?php

namespace Hubleto\App\External\WaiBlue\VehicleLog\Controllers;

class Vehicles extends \Hubleto\Erp\Controller
{
  public function getBreadcrumbs(): array
  {
    return array_merge(parent::getBreadcrumbs(), [
      ['url' => 'vehicle-log', 'content' => $this->translate('Vehicle log')],
      ['url' => 'vehicle-log/vehicles', 'content' => $this->translate('Vehicles')],
    ]);
  }

  public function prepareView(): void
  {
    parent::prepareView();
    $this->viewParams['app'] = $this->appManager()->getApp(\Hubleto\App\External\WaiBlue\VehicleLog\Loader::class);
    $this->setView('@Hubleto:App:External:WaiBlue:VehicleLog/Vehicles.twig');
  }
}
