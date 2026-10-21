# Hubleto Vehicle Log

External Hubleto application for recording business and private vehicle trips.

## Features

- vehicle registry with registration number, VIN, propulsion and consumption data;
- trip date, sequence number, driver, time, route, purpose and odometer data;
- automatic duration, distance, business/private split and expected energy consumption calculations;
- petrol, diesel, LPG, CNG, hybrid, plug-in hybrid and electric vehicles;
- an interactive OpenStreetMap route picker directly in the trip form;
- reverse geocoding of selected points to real place names;
- all business fields are optional, so a route can be saved before a vehicle or other details are known;
- standard Hubleto tables, forms, date/time inputs and second sidebar navigation.

The package has no frontend build step and does not require Leaflet or a Google Maps API key. It uses Hubleto's standard table, form, modal and input components. Map tiles are loaded from OpenStreetMap and reverse geocoding uses the public Nominatim service. Deployments with heavier traffic should configure their own tile/geocoding service in accordance with the providers' usage policies.

## Installation

After the package is published on Packagist:

```bash
composer require wai-blue/hubleto-vehicle-log
php hubleto app install "Hubleto\\App\\External\\WaiBlue\\VehicleLog"
```

For local development, add this repository as a Composer `path` repository in the Hubleto project's `composer.json`, require the package, and run the same Hubleto install command.

The app intentionally uses the existing `vehicle_log_vehicles` and `vehicle_log_trips` table names. Installing this external version therefore keeps data created by the former enterprise implementation.

## License

GPL-3.0-only.
