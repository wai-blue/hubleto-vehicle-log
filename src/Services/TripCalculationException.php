<?php

namespace Hubleto\App\External\WaiBlue\VehicleLog\Services;

class TripCalculationException extends \InvalidArgumentException
{
  public function __construct(
    public readonly string $field,
    string $message,
  ) {
    parent::__construct($message);
  }
}
