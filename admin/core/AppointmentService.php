<?php

declare(strict_types=1);

class AppointmentService
{
    public static function create(array $data, int $adminId): int
    {
        $clientId = (int) ($data['client_id'] ?? 0);
        $title    = trim($data['title'] ?? '');

        if ($clientId <= 0 || $title === '') {
            throw new RuntimeException('Client and appointment title are required.');
        }

        $client = ClientService::getById($clientId);
        if (!$client) {
            throw new RuntimeException('Client not found.');
        }

        $startsAt = normalizeDateTimeInput(trim($data['starts_at'] ?? ''));
        $endsAt   = normalizeDateTimeInput(trim($data['ends_at'] ?? ''));

        if ($startsAt === '') {
            throw new RuntimeException('Start date and time are required.');
        }

        if ($endsAt === '') {
            $endsAt = date('Y-m-d H:i:s', strtotime($startsAt . ' +1 hour'));
        }

        $caseId      = !empty($data['case_id']) ? (int) $data['case_id'] : null;
        $description = trim($data['description'] ?? '') ?: null;
        $location    = trim($data['location'] ?? '') ?: null;
        $status      = $data['status'] ?? 'scheduled';

        try {
            $id = Database::insert(
                'INSERT INTO appointments (case_id, client_id, admin_id, title, description, starts_at, ends_at, location, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
                [$caseId, $clientId, $adminId, $title, $description, $startsAt, $endsAt, $location, $status]
            );
        } catch (Throwable $e) {
            $id = Database::insert(
                'INSERT INTO appointments (case_id, client_id, admin_id, title, description, start_time, end_time, location, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
                [$caseId, $clientId, $adminId, $title, $description, $startsAt, $endsAt, $location, $status]
            );
        }

        $appointment = self::getById($id);
        $calendar    = GoogleCalendarService::syncAppointment($id, $client);

        if (!empty($calendar['url'])) {
            $appointment['meeting_link'] = $calendar['url'];
        }
        if (!empty($calendar['ics_url'])) {
            $appointment['ics_url'] = $calendar['ics_url'];
        }

        self::notifyAppointment($client, $appointment ?? ['title' => $title, 'starts_at' => $startsAt, 'ends_at' => $endsAt, 'location' => $location, 'description' => $description], $calendar);

        return $id;
    }

    public static function update(int $id, array $data): void
    {
        $appointment = self::getById($id);
        if (!$appointment) {
            throw new RuntimeException('Appointment not found.');
        }

        $title = trim($data['title'] ?? $appointment['title'] ?? '');
        if ($title === '') {
            throw new RuntimeException('Title is required.');
        }

        $startsAt = normalizeDateTimeInput(trim($data['starts_at'] ?? appointmentStart($appointment) ?? ''));
        $endsAt   = normalizeDateTimeInput(trim($data['ends_at'] ?? appointmentEnd($appointment) ?? ''));

        if ($startsAt === '') {
            throw new RuntimeException('Start date and time are required.');
        }

        if ($endsAt === '') {
            $endsAt = date('Y-m-d H:i:s', strtotime($startsAt . ' +1 hour'));
        }

        $fields = [
            'title'       => $title,
            'description' => trim($data['description'] ?? '') ?: null,
            'location'    => trim($data['location'] ?? '') ?: null,
            'status'      => $data['status'] ?? $appointment['status'] ?? 'scheduled',
            'starts_at'   => $startsAt,
            'ends_at'     => $endsAt,
        ];

        try {
            Database::query(
                'UPDATE appointments SET title = ?, description = ?, location = ?, status = ?, starts_at = ?, ends_at = ?, updated_at = NOW() WHERE id = ?',
                [$fields['title'], $fields['description'], $fields['location'], $fields['status'], $fields['starts_at'], $fields['ends_at'], $id]
            );
        } catch (Throwable $e) {
            Database::query(
                'UPDATE appointments SET title = ?, description = ?, location = ?, status = ?, start_time = ?, end_time = ?, updated_at = NOW() WHERE id = ?',
                [$fields['title'], $fields['description'], $fields['location'], $fields['status'], $fields['starts_at'], $fields['ends_at'], $id]
            );
        }

        $client = ClientService::getById((int) ($appointment['client_id'] ?? 0));
        if ($client) {
            GoogleCalendarService::syncAppointment($id, $client);
        }
    }

    public static function cancel(int $id): void
    {
        $appointment = self::getById($id);
        if (!$appointment) {
            throw new RuntimeException('Appointment not found.');
        }

        try {
            Database::query("UPDATE appointments SET status = 'cancelled', updated_at = NOW() WHERE id = ?", [$id]);
        } catch (Throwable $e) {
            throw new RuntimeException('Unable to cancel appointment.');
        }
    }

    public static function getCalendarResultMessage(array $calendar): string
    {
        return $calendar['message'] ?? ($calendar['success'] ? 'Synced to Google Calendar.' : '');
    }

    public static function getById(int $id): ?array
    {
        $row = Database::fetch(
            'SELECT a.*, a.starts_at AS start_time, a.ends_at AS end_time FROM appointments a WHERE a.id = ?',
            [$id]
        );

        if (!$row) {
            $row = Database::fetch('SELECT * FROM appointments WHERE id = ?', [$id]);
        }

        return $row;
    }

    public static function getCasesForClient(int $clientId): array
    {
        return Database::fetchAll(
            "SELECT id, case_number, title FROM cases WHERE client_id = ? ORDER BY updated_at DESC",
            [$clientId]
        );
    }

    private static function notifyAppointment(array $client, array $appointment, array $calendar = []): void
    {
        if (!empty($client['email'])) {
            MailService::sendAppointmentEmail($client, $appointment, $calendar['url'] ?? null);
        }

        $userId = (int) ($client['user_id'] ?? 0);
        $start  = formatDateTime(appointmentStart($appointment));

        if ($userId > 0) {
            $link = $calendar['url'] ?? clientUrl('pages/dashboard.php');

            try {
                Database::insert(
                    'INSERT INTO notifications (user_id, title, message, type, is_read, link, created_at) VALUES (?, ?, ?, ?, 0, ?, NOW())',
                    [
                        $userId,
                        'Appointment scheduled',
                        ($appointment['title'] ?? 'Appointment') . ' — ' . $start,
                        'appointment',
                        $link,
                    ]
                );
            } catch (Throwable $e) {
                // optional
            }
        }

        foreach (Database::fetchAll("SELECT id FROM users WHERE role = 'admin' AND status = 'active'") as $admin) {
            try {
                Database::insert(
                    'INSERT INTO notifications (user_id, title, message, type, is_read, link, created_at) VALUES (?, ?, ?, ?, 0, ?, NOW())',
                    [
                        (int) $admin['id'],
                        'Appointment scheduled',
                        clientFullName($client) . ' — ' . ($appointment['title'] ?? 'Appointment') . ' (' . $start . ')',
                        'appointment',
                        url('pages/appointments.php'),
                    ]
                );
            } catch (Throwable $e) {
                // optional
            }
        }
    }
}
