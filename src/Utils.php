<?php

namespace GlpiPlugin\Reservationdetails;

class Utils {
    public static function formatToBr(?string $date): string {
        if (empty($date)) {
            return '';
        }

        try {
            $d = new \DateTime($date);
            return $d->format('d/m/Y H:i');
        } catch (\Exception $e) {
            return $date;
        }
    }

    public static function calculateEndDate(string $startDate, int $seconds): string {
        try {
            $date = new \DateTimeImmutable($startDate);

            return $date->modify("+{$seconds} seconds")->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return $startDate;
        }
    }
}
