<?php

namespace Hubleto\App\External\WaiBlue\VehicleLog;

/**
 * Vehicle bookings in the Hubleto calendar. Everyone sees them, so it is clear which vehicle is taken.
 */
class Calendar extends \Hubleto\App\Community\Calendar\Calendar
{
  const COLOR = '#9930f0';

  public function getCalendarConfig(): array
  {
    return [
      'position' => 9,
      'color' => self::COLOR,
      'title' => $this->translate('Vehicle bookings'),
      'icon' => 'fas fa-car',
    ];
  }

  public function loadEvent(int $id): array
  {
    $booking = $this->getModel(Models\Booking::class)->record->prepareReadQuery()->where('vehicle_log_bookings.id', $id)->first();
    if (!$booking) return [];
    return $booking->toArray();
  }

  public function loadEvents(string $dateStart, string $dateEnd, array $filter = []): array
  {
    /** @var Models\Booking */
    $mBooking = $this->getModel(Models\Booking::class);
    $query = $mBooking->record->prepareReadQuery()
      ->where('vehicle_log_bookings.date_start', '<=', $dateEnd)
      ->where('vehicle_log_bookings.date_end', '>=', $dateStart)
    ;

    $idSelectedUser = (int) ($filter['idUser'] ?? 0);
    $showsOnlyMyBookings = (int) ($filter['fOwnership'] ?? 0) == 1;
    if ($showsOnlyMyBookings) $idSelectedUser = (int) $this->authProvider()->getUserId();
    if ($idSelectedUser > 0) {
      $query = $query->where('vehicle_log_bookings.id_driver', $idSelectedUser);
    }

    $events = [];
    foreach ($query->get()->toArray() as $booking) {
      $events[] = $this->convertToCalendarEvent($booking);
    }
    return $events;
  }

  private function convertToCalendarEvent(array $booking): array
  {
    $timeStart = (string) ($booking['time_start'] ?? '');
    $timeEnd = (string) ($booking['time_end'] ?? '');
    $isAllDay = $timeStart == '' && $timeEnd == '';

    $start = $booking['date_start'];
    $end = $booking['date_end'];
    if ($isAllDay) {
      // The calendar ends an all-day event at the start of the next day.
      $end = date('Y-m-d', strtotime($booking['date_end'] . ' +1 day'));
    } else {
      // A missing time means the start or the end of that day.
      $start .= ' ' . ($timeStart == '' ? '00:00:00' : $timeStart);
      $end .= ' ' . ($timeEnd == '' ? '23:59:59' : $timeEnd);
    }

    return [
      'id' => (int) $booking['id'],
      'start' => $start,
      'end' => $end,
      'allDay' => $isAllDay,
      'title' => (string) $booking['name'],
      'color' => self::COLOR,
      'source' => 'vehicle-log-bookings',
      'id_owner' => $booking['id_driver'],
      'url' => 'vehicle-log/bookings/' . $booking['id'],
    ];
  }
}
