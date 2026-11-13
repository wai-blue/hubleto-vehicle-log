# Hubleto Vehicle Log

External Hubleto application for recording business and private vehicle trips and for booking vehicles.

## Features

- vehicle registry with registration number, VIN, propulsion and consumption data;
- trip date, sequence number, driver, time, route, purpose and odometer data;
- automatic duration, distance, business/private split and expected energy consumption calculations;
- petrol, diesel, LPG, CNG, hybrid, plug-in hybrid and electric vehicles;
- an interactive OpenStreetMap route picker directly in the trip form;
- reverse geocoding of selected points to real place names;
- all business fields are optional, so a route can be saved before a vehicle or other details are known;
- vehicle bookings (who has the vehicle from when to when), a "Bookings" tab in the vehicle form and a
  "Vehicle bookings" calendar in the Hubleto Calendar; no double bookings; a vehicle with bookings cannot be deleted;
- standard Hubleto tables, forms, date/time inputs and app navigation.

The app uses Hubleto's built-in React components, its own React form for the vehicle
with the bookings tab (built with the project's `npm run build`), and its own
JavaScript and CSS for the trip map. It does not require Leaflet or a Google Maps API key.

## Installation

Requires an existing Hubleto 2 development installation with the Auth, Settings,
Desktop and Calendar apps. The Composer package currently depends on `hubleto/erp:dev-main`.

After registration on Packagist, run these commands from the Hubleto project root:

```bash
composer require wai-blue/hubleto-vehicle-log
php hubleto app install 'Hubleto\App\External\WaiBlue\VehicleLog'
npm install && npm run build
```

Open **Vehicle log** in the sidebar, or visit `/vehicle-log`.

Until Packagist registration, use a local Composer `path` repository as described
in the [external app tutorial](https://developer.hubleto.eu/v2/tutorials/create-first-external-app).

The app uses the `vehicle_log_vehicles` and `vehicle_log_trips` tables shared with
the former enterprise version. Do not enable both versions together. Before
switching an existing installation, back up the database and review its schema,
app configuration and migration history; these steps describe a new installation.

## Usage

1. Open **Vehicle log > Vehicles** (`/vehicle-log/vehicles`) and add a vehicle.
2. Set its propulsion type and fuel or electricity consumption where applicable.
3. Open **Vehicle log > Trips** (`/vehicle-log`) and click **Add trip**.
4. Select a vehicle and driver, then enter the date, times, route and purpose.
5. Enter the odometer readings and business/private kilometres where known.
6. Save the trip. Hubleto calculates the duration, distance and expected energy
   consumption from the supplied values.

A trip can also be saved before a vehicle or other business details are known.
The map lets you select its start and end points and fills in their coordinates
and place names. It does not calculate driving directions or road distance;
distance is calculated from the odometer readings or the kilometre split.

## Bookings

1. Open a vehicle and its **Bookings** tab, and click **Book the vehicle**; or open
   **Vehicle log > Bookings** (`/vehicle-log/bookings`) and click **Book a vehicle**.
2. Enter the start and end date (a booking may last several days), optionally the
   times, the driver and a note. A booking without a name is named after its vehicle.
3. Bookings are shown in the Hubleto Calendar as "Vehicle bookings", for every user.

A booking cannot end before it starts, and a vehicle can be booked only once at a time
(a booking may start when another one ends). Bookings are stored in `vehicle_log_bookings`.

## Map Services

The map needs internet access. Tiles are loaded from OpenStreetMap, and selected
coordinates are sent to the public Nominatim service to resolve place names.
If reverse geocoding is unavailable, the selected coordinates remain available.

Use these services in accordance with the
[OpenStreetMap tile policy](https://operations.osmfoundation.org/policies/tiles/)
and [Nominatim usage policy](https://operations.osmfoundation.org/policies/nominatim/).

## License

GPL-3.0-only.
