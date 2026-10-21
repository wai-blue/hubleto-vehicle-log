<?php

namespace Hubleto\App\External\WaiBlue\VehicleLog\Services;

class TripCalculator
{
  private const NON_NEGATIVE_FIELDS = [
    'odometer_start_km',
    'odometer_end_km',
    'distance_km',
    'business_km',
    'private_km',
    'fuel_purchased_l',
    'electricity_purchased_kwh',
    'purchase_price_incl_vat',
  ];

  public static function calculate(array $record, ?array $vehicle = null, int $nextSequence = 0): array
  {
    foreach (self::NON_NEGATIVE_FIELDS as $field) {
      if (self::hasNumber($record, $field) && (float) $record[$field] < 0) {
        throw new TripCalculationException($field, self::label($field) . ' cannot be negative.');
      }
    }

    if ($nextSequence > 0 && !self::hasNumber($record, 'sequence_number')) {
      $record['sequence_number'] = $nextSequence;
    }

    if (empty($record['datetime_created'])) {
      $record['datetime_created'] = date('Y-m-d H:i:s');
    }

    if (!empty($record['time_start']) && !empty($record['time_end'])) {
      $record['duration_minutes'] = self::durationMinutes((string) $record['time_start'], (string) $record['time_end']);
    } else {
      $record['duration_minutes'] = null;
    }

    $hasOdometerStart = self::hasNumber($record, 'odometer_start_km');
    $hasOdometerEnd = self::hasNumber($record, 'odometer_end_km');

    if ($hasOdometerStart && $hasOdometerEnd) {
      $start = (float) $record['odometer_start_km'];
      $end = (float) $record['odometer_end_km'];
      if ($end < $start) {
        throw new TripCalculationException('odometer_end_km', 'Odometer at end cannot be lower than odometer at start.');
      }
      $record['distance_km'] = round($end - $start, 1);
    } elseif (!self::hasNumber($record, 'distance_km')) {
      $split = self::number($record, 'business_km') + self::number($record, 'private_km');
      $record['distance_km'] = $split > 0 ? round($split, 1) : null;
    }

    if (self::hasNumber($record, 'distance_km')) {
      $distance = round((float) $record['distance_km'], 1);
      $business = self::number($record, 'business_km');
      $private = self::number($record, 'private_km');

      if ($business <= 0 && $private <= 0) {
        $business = $distance;
      } elseif ($business > 0 && $private <= 0) {
        $private = max(0, $distance - $business);
      } elseif ($private > 0 && $business <= 0) {
        $business = max(0, $distance - $private);
      }

      if (abs(($business + $private) - $distance) > 0.11) {
        throw new TripCalculationException('business_km', 'Business and private kilometres must equal the trip distance.');
      }

      $record['distance_km'] = $distance;
      $record['business_km'] = round($business, 1);
      $record['private_km'] = round($private, 1);
    }

    $distance = self::number($record, 'distance_km');
    $fuelNorm = self::number($vehicle ?? [], 'fuel_consumption_l_per_100km');
    $electricNorm = self::number($vehicle ?? [], 'electric_consumption_kwh_per_100km');

    $record['expected_fuel_consumption_l'] = $distance > 0 && $fuelNorm > 0
      ? round($distance * $fuelNorm / 100, 2)
      : null;
    $record['expected_electricity_consumption_kwh'] = $distance > 0 && $electricNorm > 0
      ? round($distance * $electricNorm / 100, 2)
      : null;

    return $record;
  }

  private static function durationMinutes(string $start, string $end): int
  {
    $startSeconds = strtotime('1970-01-01 ' . $start . ' UTC');
    $endSeconds = strtotime('1970-01-01 ' . $end . ' UTC');
    if ($startSeconds === false || $endSeconds === false) {
      throw new TripCalculationException('time_end', 'Start or end time is invalid.');
    }
    if ($endSeconds < $startSeconds) {
      $endSeconds += 86400;
    }

    return (int) round(($endSeconds - $startSeconds) / 60);
  }

  private static function hasNumber(array $record, string $field): bool
  {
    return array_key_exists($field, $record) && $record[$field] !== null && $record[$field] !== '' && is_numeric($record[$field]);
  }

  private static function number(array $record, string $field): float
  {
    return self::hasNumber($record, $field) ? (float) $record[$field] : 0.0;
  }

  private static function label(string $field): string
  {
    return ucfirst(str_replace('_', ' ', $field));
  }
}
