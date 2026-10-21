<?php

namespace Hubleto\App\External\WaiBlue\VehicleLog\Services;

class NominatimReverseGeocoder
{
  private const ENDPOINT = 'https://nominatim.openstreetmap.org/reverse';
  private const USER_AGENT = 'HubletoVehicleLog/1.0 (+https://github.com/wai-blue/hubleto-vehicle-log)';
  private const CACHE_TTL = 2592000;

  public function reverse(float $latitude, float $longitude): string
  {
    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
      throw new \InvalidArgumentException('Coordinates are outside the valid range.');
    }

    $fallback = number_format($latitude, 7, '.', '') . ', ' . number_format($longitude, 7, '.', '');
    $cacheFile = sys_get_temp_dir() . '/hubleto-vehicle-log-geocode-' . sha1(round($latitude, 6) . ':' . round($longitude, 6)) . '.json';
    if (is_file($cacheFile) && filemtime($cacheFile) >= time() - self::CACHE_TTL) {
      $cached = json_decode((string) file_get_contents($cacheFile), true);
      if (is_array($cached) && !empty($cached['address'])) {
        return (string) $cached['address'];
      }
    }

    $query = http_build_query([
      'format' => 'jsonv2',
      'lat' => $latitude,
      'lon' => $longitude,
      'zoom' => 18,
      'addressdetails' => 1,
      'accept-language' => 'sk,en',
    ]);

    $response = $this->request(self::ENDPOINT . '?' . $query);
    $data = json_decode($response, true);
    if (!is_array($data)) {
      return $fallback;
    }

    $address = $this->formatAddress((array) ($data['address'] ?? []), (string) ($data['display_name'] ?? ''));
    if ($address === '') {
      $address = $fallback;
    }

    @file_put_contents($cacheFile, json_encode(['address' => $address], JSON_UNESCAPED_UNICODE));
    return $address;
  }

  private function request(string $url): string
  {
    $lockPath = sys_get_temp_dir() . '/hubleto-vehicle-log-nominatim.lock';
    $lock = @fopen($lockPath, 'c+');
    if (is_resource($lock)) {
      flock($lock, LOCK_EX);
      $lastRequest = (float) trim((string) stream_get_contents($lock));
      $waitMicroseconds = (int) max(0, 1000000 - ((microtime(true) - $lastRequest) * 1000000));
      if ($waitMicroseconds > 0) {
        usleep($waitMicroseconds);
      }
    }

    try {
      if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_CONNECTTIMEOUT => 5,
          CURLOPT_TIMEOUT => 10,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_USERAGENT => self::USER_AGENT,
          CURLOPT_HTTPHEADER => ['Referer: https://github.com/wai-blue/hubleto-vehicle-log'],
        ]);
        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        if (is_string($response) && $status >= 200 && $status < 300) {
          return $response;
        }
      }

      $context = stream_context_create(['http' => [
        'timeout' => 10,
        'header' => "User-Agent: " . self::USER_AGENT . "\r\nReferer: https://github.com/wai-blue/hubleto-vehicle-log\r\n",
      ]]);
      $response = @file_get_contents($url, false, $context);
      return is_string($response) ? $response : '';
    } finally {
      if (is_resource($lock)) {
        ftruncate($lock, 0);
        rewind($lock);
        fwrite($lock, (string) microtime(true));
        fflush($lock);
        flock($lock, LOCK_UN);
        fclose($lock);
      }
    }
  }

  private function formatAddress(array $address, string $displayName): string
  {
    $road = $address['road'] ?? $address['pedestrian'] ?? $address['footway'] ?? $address['residential'] ?? '';
    $houseNumber = $address['house_number'] ?? '';
    $locality = $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? $address['county'] ?? '';
    $postcode = $address['postcode'] ?? '';
    $country = $address['country'] ?? '';

    $street = trim($road . ($houseNumber !== '' ? ' ' . $houseNumber : ''));
    $place = trim($postcode . ($locality !== '' ? ' ' . $locality : ''));
    $parts = array_values(array_unique(array_filter([$street, $place, $country])));

    return count($parts) > 0 ? implode(', ', $parts) : $displayName;
  }
}
