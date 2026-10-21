<?php

namespace Hubleto\App\External\WaiBlue\VehicleLog\Controllers\Api;

use Hubleto\App\External\WaiBlue\VehicleLog\Services\NominatimReverseGeocoder;

class ReverseGeocode extends \Hubleto\Erp\Controllers\ApiController
{
  public function response(): array
  {
    $latitude = $this->router()->urlParamAsFloat('latitude');
    $longitude = $this->router()->urlParamAsFloat('longitude');

    $address = $this->getService(NominatimReverseGeocoder::class)->reverse($latitude, $longitude);

    return [
      'status' => 'success',
      'address' => $address,
      'latitude' => $latitude,
      'longitude' => $longitude,
    ];
  }
}
