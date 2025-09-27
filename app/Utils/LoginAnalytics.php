<?php
namespace App\Utils;

use ORM;
use App\Utils\LogUtil;
use App\Utils\LogType;

/**
 * Login Analytics für erweiterte Heatmaps und Trend-Analyse
 * Phase 1: Quick Wins Implementation
 */
class LoginAnalytics 
{
    /**
     * Heatmap-Daten für Login-Verteilung nach Stunden/Wochentagen
     */
    public static function getLoginHeatmapData(int $days = 30): array {
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $endDate = date('Y-m-d H:i:s');
        
        // Erfolgreiche Logins holen
        $successfulLogins = ORM::for_table('logs')
            ->where('type', 'AUDIT')
            ->where_like('context', '%login%')
            ->where_like('message', '%SUCCESS%')
            ->where_gte('datum_zeit', $startDate)
            ->where_lte('datum_zeit', $endDate)
            ->select('datum_zeit')
            ->find_array();
        
        // Heatmap-Matrix erstellen: [Wochentag][Stunde] = Anzahl
        $heatmapData = [];
        
        // Initialisiere alle 7 Tage x 24 Stunden mit 0
        for ($day = 0; $day <= 6; $day++) {
            for ($hour = 0; $hour <= 23; $hour++) {
                $heatmapData[$day][$hour] = 0;
            }
        }
        
        // Logins in Heatmap einordnen
        foreach ($successfulLogins as $login) {
            $timestamp = strtotime($login['datum_zeit']);
            $dayOfWeek = (int)date('w', $timestamp); // 0=Sonntag, 6=Samstag
            $hourOfDay = (int)date('H', $timestamp); // 0-23
            
            $heatmapData[$dayOfWeek][$hourOfDay]++;
        }
        
        return [
            'heatmap_matrix' => $heatmapData,
            'total_logins' => count($successfulLogins),
            'analysis_period_days' => $days,
            'peak_activity' => self::findPeakActivity($heatmapData),
            'quiet_periods' => self::findQuietPeriods($heatmapData)
        ];
    }
    
    /**
     * Stündliche Login-Verteilung für Charts
     */
    public static function getHourlyLoginDistribution(int $days = 7): array {
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $hourlyData = [];
        for ($hour = 0; $hour <= 23; $hour++) {
            $hourlyData[$hour] = [
                'hour' => $hour,
                'successful_logins' => 0,
                'failed_logins' => 0,
                'hour_label' => sprintf('%02d:00', $hour)
            ];
        }
        
        // Erfolgreiche Logins
        $successfulLogins = ORM::for_table('logs')
            ->where('type', 'AUDIT')
            ->where_like('context', '%login%')
            ->where_like('message', '%SUCCESS%')
            ->where_gte('datum_zeit', $startDate)
            ->select('datum_zeit')
            ->find_array();
            
        foreach ($successfulLogins as $login) {
            $hour = (int)date('H', strtotime($login['datum_zeit']));
            $hourlyData[$hour]['successful_logins']++;
        }
        
        // Failed Logins
        $failedLogins = ORM::for_table('logs')
            ->where('type', 'AUDIT')
            ->where_like('context', '%login%')
            ->where_like('message', '%FAILED%')
            ->where_gte('datum_zeit', $startDate)
            ->select('datum_zeit')
            ->find_array();
            
        foreach ($failedLogins as $login) {
            $hour = (int)date('H', strtotime($login['datum_zeit']));
            $hourlyData[$hour]['failed_logins']++;
        }
        
        return array_values($hourlyData);
    }
    
    /**
     * Wöchentliche Trends
     */
    public static function getWeeklyTrends(int $weeks = 4): array {
        $weeklyData = [];
        
        for ($week = $weeks - 1; $week >= 0; $week--) {
            $weekStart = date('Y-m-d H:i:s', strtotime("-{$week} weeks Monday"));
            $weekEnd = date('Y-m-d H:i:s', strtotime("-{$week} weeks Sunday 23:59:59"));
            
            $successfulLogins = ORM::for_table('logs')
                ->where('type', 'AUDIT')
                ->where_like('context', '%login%')
                ->where_like('message', '%SUCCESS%')
                ->where_gte('datum_zeit', $weekStart)
                ->where_lte('datum_zeit', $weekEnd)
                ->count();
                
            $failedLogins = ORM::for_table('logs')
                ->where('type', 'AUDIT')
                ->where_like('context', '%login%')
                ->where_like('message', '%FAILED%')
                ->where_gte('datum_zeit', $weekStart)
                ->where_lte('datum_zeit', $weekEnd)
                ->count();
            
            $weeklyData[] = [
                'week_start' => date('M d', strtotime($weekStart)),
                'week_end' => date('M d', strtotime($weekEnd)),
                'successful_logins' => $successfulLogins,
                'failed_logins' => $failedLogins,
                'success_rate' => $successfulLogins + $failedLogins > 0 ? 
                    round(($successfulLogins / ($successfulLogins + $failedLogins)) * 100, 1) : 100
            ];
        }
        
        return $weeklyData;
    }
    
    /**
     * Peak Activity Zeiten finden
     */
    private static function findPeakActivity(array $heatmapData): array {
        $maxLogins = 0;
        $peakTimes = [];
        
        foreach ($heatmapData as $day => $hours) {
            foreach ($hours as $hour => $loginCount) {
                if ($loginCount > $maxLogins) {
                    $maxLogins = $loginCount;
                    $peakTimes = [['day' => $day, 'hour' => $hour, 'count' => $loginCount]];
                } elseif ($loginCount === $maxLogins && $maxLogins > 0) {
                    $peakTimes[] = ['day' => $day, 'hour' => $hour, 'count' => $loginCount];
                }
            }
        }
        
        return [
            'max_logins' => $maxLogins,
            'peak_times' => $peakTimes,
            'peak_description' => self::describePeakTimes($peakTimes)
        ];
    }
    
    /**
     * Ruhige Zeiten finden (wenig Activity)
     */
    private static function findQuietPeriods(array $heatmapData): array {
        $allCounts = [];
        foreach ($heatmapData as $day => $hours) {
            foreach ($hours as $hour => $count) {
                if ($count > 0) { // Nur Zeiten mit Login-Activity
                    $allCounts[] = $count;
                }
            }
        }
        
        if (empty($allCounts)) {
            return ['quiet_threshold' => 0, 'quiet_periods' => []];
        }
        
        $avgLogins = array_sum($allCounts) / count($allCounts);
        $quietThreshold = max(1, (int)($avgLogins * 0.2)); // 20% des Durchschnitts
        
        $quietPeriods = [];
        foreach ($heatmapData as $day => $hours) {
            foreach ($hours as $hour => $count) {
                if ($count <= $quietThreshold && $count >= 0) {
                    $quietPeriods[] = [
                        'day' => $day,
                        'hour' => $hour,
                        'count' => $count,
                        'day_name' => self::getDayName($day),
                        'hour_label' => sprintf('%02d:00', $hour)
                    ];
                }
            }
        }
        
        return [
            'quiet_threshold' => $quietThreshold,
            'average_logins' => round($avgLogins, 1),
            'quiet_periods' => $quietPeriods
        ];
    }
    
    /**
     * Hilfsmethode: Peak Times beschreiben
     */
    private static function describePeakTimes(array $peakTimes): string {
        if (empty($peakTimes)) {
            return 'No peak activity detected';
        }
        
        $descriptions = [];
        foreach ($peakTimes as $peak) {
            $dayName = self::getDayName($peak['day']);
            $hourLabel = sprintf('%02d:00', $peak['hour']);
            $descriptions[] = "{$dayName} at {$hourLabel} ({$peak['count']} logins)";
        }
        
        return implode(', ', $descriptions);
    }
    
    /**
     * Hilfsmethode: Wochentag-Namen
     */
    private static function getDayName(int $dayOfWeek): string {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return $days[$dayOfWeek] ?? 'Unknown';
    }
    
    /**
     * Login-Pattern-Analyse für Security
     */
    public static function detectUnusualLoginPatterns(): array {
        $unusual = [];
        
        // 1. Logins zu ungewöhnlichen Zeiten (2-6 AM)
        $nightLogins = ORM::for_table('logs')
            ->where('type', 'AUDIT')
            ->where_like('context', '%login%')
            ->where_like('message', '%SUCCESS%')
            ->where_gte('datum_zeit', date('Y-m-d H:i:s', strtotime('-7 days')))
            ->find_array();
        
        $nightLoginCount = 0;
        foreach ($nightLogins as $login) {
            $hour = (int)date('H', strtotime($login['datum_zeit']));
            if ($hour >= 2 && $hour <= 6) {
                $nightLoginCount++;
            }
        }
        
        if ($nightLoginCount > 5) {
            $unusual[] = [
                'type' => 'unusual_hours',
                'description' => "Unusual night logins detected: {$nightLoginCount} logins between 2-6 AM",
                'severity' => 'medium',
                'count' => $nightLoginCount
            ];
        }
        
        // 2. Weekend-Activity (könnte automatisierte Angriffe sein)
        $weekendLogins = 0;
        foreach ($nightLogins as $login) {
            $dayOfWeek = (int)date('w', strtotime($login['datum_zeit']));
            if ($dayOfWeek == 0 || $dayOfWeek == 6) { // Sonntag oder Samstag
                $weekendLogins++;
            }
        }
        
        if ($weekendLogins > 10) {
            $unusual[] = [
                'type' => 'weekend_activity',
                'description' => "High weekend login activity: {$weekendLogins} logins",
                'severity' => 'low',
                'count' => $weekendLogins
            ];
        }
        
        return $unusual;
    }
}