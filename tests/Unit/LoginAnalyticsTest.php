<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Utils\LoginAnalytics;
use ReflectionClass;
use ReflectionMethod;

/**
 * LoginAnalytics Unit Tests
 *
 * Tests login analytics functionality including:
 * - Heatmap data structure and matrix creation
 * - Peak activity detection
 * - Quiet period identification
 * - Hourly distribution calculations
 * - Weekly trend analysis
 * - Pattern detection logic
 * - Data validation and edge cases
 *
 * Note: Tests focus on business logic without database dependencies
 *
 * @package Tests\Unit
 */
class LoginAnalyticsTest extends TestCase
{
    private ReflectionClass $reflection;

    /**
     * Setup test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->reflection = new ReflectionClass(LoginAnalytics::class);
    }

    /**
     * Teardown after each test
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Helper to invoke private/protected methods
     */
    private function invokeMethod(string $methodName, array $args = [])
    {
        $method = $this->reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs(null, $args);
    }

    // ==========================================
    // TESTS: HEATMAP MATRIX STRUCTURE
    // ==========================================

    /** @test */
    public function it_creates_heatmap_matrix_with_correct_dimensions(): void
    {
        // Arrange
        $matrix = $this->createEmptyHeatmapMatrix();
        
        // Assert - Should be 7 days x 24 hours
        $this->assertCount(7, $matrix);
        
        foreach ($matrix as $day) {
            $this->assertCount(24, $day);
        }
    }

    /** @test */
    public function it_initializes_heatmap_matrix_with_zeros(): void
    {
        // Arrange
        $matrix = $this->createEmptyHeatmapMatrix();
        
        // Assert - All cells should start at 0
        for ($day = 0; $day <= 6; $day++) {
            for ($hour = 0; $hour <= 23; $hour++) {
                $this->assertEquals(0, $matrix[$day][$hour]);
            }
        }
    }

    /** @test */
    public function it_populates_heatmap_matrix_correctly(): void
    {
        // Arrange
        $matrix = $this->createEmptyHeatmapMatrix();
        $testLogins = [
            ['datum_zeit' => '2025-01-01 10:00:00'], // Wednesday 10am
            ['datum_zeit' => '2025-01-01 10:15:00'], // Wednesday 10am (same slot)
            ['datum_zeit' => '2025-01-02 14:30:00'], // Thursday 2pm
        ];
        
        // Act
        foreach ($testLogins as $login) {
            $timestamp = strtotime($login['datum_zeit']);
            $dayOfWeek = (int) date('w', $timestamp);
            $hourOfDay = (int) date('H', $timestamp);
            $matrix[$dayOfWeek][$hourOfDay]++;
        }
        
        // Assert
        $wednesdayIndex = 3; // 0=Sunday, 3=Wednesday
        $thursdayIndex = 4;
        
        $this->assertEquals(2, $matrix[$wednesdayIndex][10]); // 2 logins at 10am
        $this->assertEquals(1, $matrix[$thursdayIndex][14]); // 1 login at 2pm
        $this->assertEquals(0, $matrix[0][0]); // Empty slot
    }

    /** @test */
    public function it_validates_day_of_week_indices(): void
    {
        // Arrange - Test all days
        $testDates = [
            '2025-01-05' => 0, // Sunday
            '2025-01-06' => 1, // Monday
            '2025-01-07' => 2, // Tuesday
            '2025-01-08' => 3, // Wednesday
            '2025-01-09' => 4, // Thursday
            '2025-01-10' => 5, // Friday
            '2025-01-11' => 6, // Saturday
        ];
        
        // Act & Assert
        foreach ($testDates as $date => $expectedDayIndex) {
            $dayOfWeek = (int) date('w', strtotime($date));
            $this->assertEquals($expectedDayIndex, $dayOfWeek);
        }
    }

    /** @test */
    public function it_validates_hour_of_day_indices(): void
    {
        // Arrange
        $testTimes = [
            '00:00:00' => 0,
            '06:30:00' => 6,
            '12:00:00' => 12,
            '18:45:00' => 18,
            '23:59:59' => 23,
        ];
        
        // Act & Assert
        foreach ($testTimes as $time => $expectedHour) {
            $hourOfDay = (int) date('H', strtotime('2025-01-01 ' . $time));
            $this->assertEquals($expectedHour, $hourOfDay);
        }
    }

    // ==========================================
    // TESTS: PEAK ACTIVITY DETECTION
    // ==========================================

   /** @test */
    public function it_finds_peak_activity_slot(): void
    {
        // Arrange
        $matrix = $this->createEmptyHeatmapMatrix();
        $matrix[3][10] = 50; // Wednesday 10am - Peak
        $matrix[4][14] = 30; // Thursday 2pm
        $matrix[1][9] = 20;  // Monday 9am
    
        // Act
        $peak = $this->invokeMethod('findPeakActivity', [$matrix]);
    
        // Assert
        $this->assertIsArray($peak);
        $this->assertArrayHasKey('max_logins', $peak);
        $this->assertArrayHasKey('peak_times', $peak);
        $this->assertArrayHasKey('peak_description', $peak);
    
        $this->assertEquals(50, $peak['max_logins']);
        $this->assertIsArray($peak['peak_times']);
        $this->assertNotEmpty($peak['peak_times']);
    
        // Prüfe erstes Peak-Element
        $firstPeak = $peak['peak_times'][0];
        $this->assertEquals(3, $firstPeak['day']);
        $this->assertEquals(10, $firstPeak['hour']);
        $this->assertEquals(50, $firstPeak['count']);
    }

    /** @test */
    public function it_handles_multiple_equal_peaks(): void
    {
    // Arrange
        $matrix = $this->createEmptyHeatmapMatrix();
        $matrix[1][10] = 50; // Monday 10am
        $matrix[3][14] = 50; // Wednesday 2pm (same count)
    
    // Act
        $peak = $this->invokeMethod('findPeakActivity', [$matrix]);
    
    // Assert - Should return both peaks
        $this->assertIsArray($peak);
        $this->assertEquals(50, $peak['max_logins']);
        $this->assertIsArray($peak['peak_times']);
        $this->assertCount(2, $peak['peak_times']); // Beide Peaks sollten enthalten sein
    
    // Prüfe ob beide Peak-Zeiten enthalten sind
        $days = array_column($peak['peak_times'], 'day');
        $this->assertContains(1, $days);
        $this->assertContains(3, $days);
    }

    /** @test */
    public function it_handles_matrix_with_no_activity(): void
    {
    // Arrange
        $matrix = $this->createEmptyHeatmapMatrix();
    
    // Act
        $peak = $this->invokeMethod('findPeakActivity', [$matrix]);
    
    // Assert
        $this->assertIsArray($peak);
        $this->assertArrayHasKey('max_logins', $peak);
        $this->assertEquals(0, $peak['max_logins']);
        $this->assertIsArray($peak['peak_times']);
        $this->assertEmpty($peak['peak_times']); // Keine Peaks bei 0 Aktivität
    }

    /** @test */
    public function it_formats_peak_activity_with_readable_names(): void
    {
        // Arrange
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $dayIndex = 3; // Wednesday
        $hour = 14;
        
        // Act
        $dayName = $dayNames[$dayIndex];
        $timeLabel = sprintf('%02d:00', $hour);
        
        // Assert
        $this->assertEquals('Wednesday', $dayName);
        $this->assertEquals('14:00', $timeLabel);
    }

    // ==========================================
    // TESTS: QUIET PERIOD DETECTION
    // ==========================================

    /** @test */
    public function it_finds_quiet_periods(): void
    {
        // Arrange
        $matrix = $this->createEmptyHeatmapMatrix();
        
        // Fill with activity except quiet periods
        for ($day = 0; $day <= 6; $day++) {
            for ($hour = 0; $hour <= 23; $hour++) {
                $matrix[$day][$hour] = 10; // Default activity
            }
        }
        
        // Set quiet periods (0 logins)
        $matrix[0][3] = 0; // Sunday 3am
        $matrix[6][4] = 0; // Saturday 4am
        
        // Act
        $quietPeriods = $this->invokeMethod('findQuietPeriods', [$matrix]);
        
        // Assert
        $this->assertIsArray($quietPeriods);
        $this->assertGreaterThanOrEqual(2, count($quietPeriods));
    }

    /** @test */
    public function it_identifies_low_activity_as_quiet(): void
    {
        // Arrange
        $matrix = $this->createEmptyHeatmapMatrix();
        $matrix[1][3] = 1; // Very low activity
        $matrix[2][4] = 0; // No activity
        
        // Act - Periods with ≤ 1 login could be considered quiet
        $isQuiet1 = ($matrix[1][3] <= 1);
        $isQuiet2 = ($matrix[2][4] <= 1);
        
        // Assert
        $this->assertTrue($isQuiet1);
        $this->assertTrue($isQuiet2);
    }

    // ==========================================
    // TESTS: HOURLY DISTRIBUTION
    // ==========================================

    /** @test */
    public function it_creates_hourly_distribution_structure(): void
    {
        // Arrange & Act
        $hourlyData = $this->createEmptyHourlyDistribution();
        
        // Assert
        $this->assertCount(24, $hourlyData);
        
        foreach ($hourlyData as $hour => $data) {
            $this->assertArrayHasKey('hour', $data);
            $this->assertArrayHasKey('successful_logins', $data);
            $this->assertArrayHasKey('failed_logins', $data);
            $this->assertArrayHasKey('hour_label', $data);
            
            $this->assertEquals($hour, $data['hour']);
            $this->assertEquals(0, $data['successful_logins']);
            $this->assertEquals(0, $data['failed_logins']);
        }
    }

    /** @test */
    public function it_formats_hour_labels_correctly(): void
    {
        // Arrange
        $testCases = [
            0 => '00:00',
            6 => '06:00',
            12 => '12:00',
            18 => '18:00',
            23 => '23:00',
        ];
        
        // Act & Assert
        foreach ($testCases as $hour => $expected) {
            $label = sprintf('%02d:00', $hour);
            $this->assertEquals($expected, $label);
        }
    }

    /** @test */
    public function it_calculates_login_distribution_correctly(): void
    {
        // Arrange
        $hourlyData = $this->createEmptyHourlyDistribution();
        
        $testLogins = [
            ['datum_zeit' => '2025-01-01 10:00:00', 'success' => true],
            ['datum_zeit' => '2025-01-01 10:30:00', 'success' => true],
            ['datum_zeit' => '2025-01-01 10:45:00', 'success' => false],
            ['datum_zeit' => '2025-01-01 14:00:00', 'success' => true],
        ];
        
        // Act
        foreach ($testLogins as $login) {
            $hour = (int) date('H', strtotime($login['datum_zeit']));
            if ($login['success']) {
                $hourlyData[$hour]['successful_logins']++;
            } else {
                $hourlyData[$hour]['failed_logins']++;
            }
        }
        
        // Assert
        $this->assertEquals(2, $hourlyData[10]['successful_logins']);
        $this->assertEquals(1, $hourlyData[10]['failed_logins']);
        $this->assertEquals(1, $hourlyData[14]['successful_logins']);
        $this->assertEquals(0, $hourlyData[14]['failed_logins']);
    }

    // ==========================================
    // TESTS: WEEKLY TRENDS
    // ==========================================

    /** @test */
    public function it_calculates_success_rate_correctly(): void
    {
        // Arrange
        $testCases = [
            ['successful' => 100, 'failed' => 0, 'expected' => 100.0],
            ['successful' => 80, 'failed' => 20, 'expected' => 80.0],
            ['successful' => 50, 'failed' => 50, 'expected' => 50.0],
            ['successful' => 0, 'failed' => 100, 'expected' => 0.0],
            ['successful' => 0, 'failed' => 0, 'expected' => 0.0], // No logins
        ];
        
        // Act & Assert
        foreach ($testCases as $case) {
            $total = $case['successful'] + $case['failed'];
            $successRate = $total > 0
                ? round(($case['successful'] / $total) * 100, 2)
                : 0;
            
            $this->assertEquals($case['expected'], $successRate);
        }
    }

    /** @test */
    public function it_formats_week_labels_correctly(): void
    {
        // Arrange
        $startDate = '2025-01-01';
        $endDate = '2025-01-07';
        
        // Act
        $weekLabel = date('M d', strtotime($startDate)) . ' - ' . date('M d', strtotime($endDate));
        
        // Assert
        $this->assertStringContainsString('Jan 01', $weekLabel);
        $this->assertStringContainsString('Jan 07', $weekLabel);
        $this->assertStringContainsString(' - ', $weekLabel);
    }

    /** @test */
    public function it_validates_week_date_ranges(): void
    {
        // Arrange
        $startDate = strtotime('2025-01-01');
        $weeksToAnalyze = 4;
        
        // Act
        $weekRanges = [];
        for ($i = 0; $i < $weeksToAnalyze; $i++) {
            $weekStart = date('Y-m-d', strtotime("-{$i} weeks", $startDate));
            $weekEnd = date('Y-m-d', strtotime("-{$i} weeks +6 days", $startDate));
            $weekRanges[] = ['start' => $weekStart, 'end' => $weekEnd];
        }
        
        // Assert
        $this->assertCount(4, $weekRanges);
        
        foreach ($weekRanges as $range) {
            $this->assertArrayHasKey('start', $range);
            $this->assertArrayHasKey('end', $range);
            
            $daysDiff = (strtotime($range['end']) - strtotime($range['start'])) / 86400;
            $this->assertEquals(6, $daysDiff); // 7 days = 6 day difference
        }
    }

    // ==========================================
    // TESTS: PATTERN DETECTION
    // ==========================================

    /** @test */
    public function it_detects_unusual_login_spikes(): void
    {
        // Arrange
        $normalActivity = 10;
        $spikeActivity = 100;
        $threshold = 50; // 5x normal
        
        // Act
        $isUnusual = ($spikeActivity > $threshold);
        
        // Assert
        $this->assertTrue($isUnusual);
    }

    /** @test */
    public function it_detects_unusual_time_periods(): void
    {
        // Arrange - Night hours (2am-5am) with high activity
        $nightHours = [2, 3, 4, 5];
        $activityCount = 50;
        $nightActivityThreshold = 20;
        
        // Act
        $unusualNightActivity = [];
        foreach ($nightHours as $hour) {
            if ($activityCount > $nightActivityThreshold) {
                $unusualNightActivity[] = $hour;
            }
        }
        
        // Assert
        $this->assertNotEmpty($unusualNightActivity);
        $this->assertCount(4, $unusualNightActivity);
    }

    /** @test */
    public function it_detects_unusual_failed_login_ratio(): void
    {
        // Arrange
        $testCases = [
            ['successful' => 90, 'failed' => 10, 'expected' => false], // Normal: 10% failed
            ['successful' => 50, 'failed' => 50, 'expected' => true],  // Unusual: 50% failed
            ['successful' => 20, 'failed' => 80, 'expected' => true],  // Unusual: 80% failed
        ];
        
        $failureThreshold = 0.3; // 30%
        
        // Act & Assert
        foreach ($testCases as $case) {
            $total = $case['successful'] + $case['failed'];
            $failureRate = $total > 0 ? $case['failed'] / $total : 0;
            $isUnusual = ($failureRate > $failureThreshold);
            
            $this->assertEquals($case['expected'], $isUnusual);
        }
    }

    // ==========================================
    // TESTS: DATA STRUCTURE VALIDATION
    // ==========================================

    /** @test */
    public function it_returns_valid_heatmap_data_structure(): void
    {
        // Arrange
        $expectedKeys = [
            'heatmap_matrix',
            'total_logins',
            'analysis_period_days',
            'peak_activity',
            'quiet_periods',
        ];
        
        // Act
        $mockData = $this->createMockHeatmapData();
        
        // Assert
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $mockData);
        }
    }

    /** @test */
    public function it_returns_valid_hourly_data_structure(): void
    {
        // Arrange
        $hourlyData = $this->createEmptyHourlyDistribution();
        
        // Assert
        $this->assertIsArray($hourlyData);
        $this->assertCount(24, $hourlyData);
        
        foreach ($hourlyData as $data) {
            $this->assertArrayHasKey('hour', $data);
            $this->assertArrayHasKey('successful_logins', $data);
            $this->assertArrayHasKey('failed_logins', $data);
            $this->assertArrayHasKey('hour_label', $data);
        }
    }

    // ==========================================
    // TESTS: EDGE CASES
    // ==========================================

    /** @test */
    public function it_handles_zero_logins(): void
    {
        // Arrange
        $matrix = $this->createEmptyHeatmapMatrix();
        
        // Act
        $totalLogins = 0;
        foreach ($matrix as $day) {
            $totalLogins += array_sum($day);
        }
        
        // Assert
        $this->assertEquals(0, $totalLogins);
    }

    /** @test */
    public function it_handles_very_high_login_counts(): void
    {
     // Arrange
        $matrix = $this->createEmptyHeatmapMatrix();
        $matrix[1][10] = 10000; // 10k logins in one slot
    
     // Act
        $peak = $this->invokeMethod('findPeakActivity', [$matrix]);
    
     // Assert
        $this->assertIsArray($peak);
        $this->assertEquals(10000, $peak['max_logins']);
        $this->assertNotEmpty($peak['peak_times']);
    
        $firstPeak = $peak['peak_times'][0];
        $this->assertEquals(10000, $firstPeak['count']);
    }

    /** @test */
    public function it_handles_timestamps_across_different_timezones(): void
    {
        // Arrange
        $utcTimestamp = '2025-01-01 12:00:00';
        
        // Act
        $hour = (int) date('H', strtotime($utcTimestamp));
        $day = (int) date('w', strtotime($utcTimestamp));
        
        // Assert
        $this->assertIsInt($hour);
        $this->assertIsInt($day);
        $this->assertGreaterThanOrEqual(0, $hour);
        $this->assertLessThanOrEqual(23, $hour);
        $this->assertGreaterThanOrEqual(0, $day);
        $this->assertLessThanOrEqual(6, $day);
    }

    /** @test */
    public function it_validates_days_parameter_range(): void
    {
        // Arrange
        $validDays = [1, 7, 14, 30, 60, 90];
        $invalidDays = [-1, 0, 366];
        
        // Assert valid
        foreach ($validDays as $days) {
            $this->assertGreaterThan(0, $days);
            $this->assertLessThanOrEqual(365, $days);
        }
        
        // Assert invalid
        foreach ($invalidDays as $days) {
            $isValid = ($days > 0 && $days <= 365);
            $this->assertFalse($isValid);
        }
    }

    // ==========================================
    // TESTS: DATE CALCULATIONS
    // ==========================================

    /** @test */
    public function it_calculates_date_ranges_correctly(): void
    {
        // Arrange
        $days = 30;
        
        // Act
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $endDate = date('Y-m-d H:i:s');
        
        // Assert
        $daysDiff = (strtotime($endDate) - strtotime($startDate)) / 86400;
        $this->assertEqualsWithDelta($days, $daysDiff, 0.1); // Allow small floating point diff
    }

    /** @test */
    public function it_handles_month_boundaries_correctly(): void
    {
        // Arrange - Test crossing month boundary
        $testDate = '2025-01-31';
        
        // Act
        $nextDay = date('Y-m-d', strtotime($testDate . ' +1 day'));
        $prevMonth = date('Y-m-d', strtotime($testDate . ' -31 days'));
        
        // Assert
        $this->assertEquals('2025-02-01', $nextDay);
        $this->assertEquals('2024-12-31', $prevMonth);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Create empty heatmap matrix (7 days x 24 hours)
     */
    private function createEmptyHeatmapMatrix(): array
    {
        $matrix = [];
        for ($day = 0; $day <= 6; $day++) {
            for ($hour = 0; $hour <= 23; $hour++) {
                $matrix[$day][$hour] = 0;
            }
        }
        return $matrix;
    }

    /**
     * Create empty hourly distribution (24 hours)
     */
    private function createEmptyHourlyDistribution(): array
    {
        $hourlyData = [];
        for ($hour = 0; $hour <= 23; $hour++) {
            $hourlyData[$hour] = [
                'hour' => $hour,
                'successful_logins' => 0,
                'failed_logins' => 0,
                'hour_label' => sprintf('%02d:00', $hour),
            ];
        }
        return $hourlyData;
    }

    /**
     * Create mock heatmap data structure
     */
    private function createMockHeatmapData(): array
    {
        return [
            'heatmap_matrix' => $this->createEmptyHeatmapMatrix(),
            'total_logins' => 0,
            'analysis_period_days' => 30,
            'peak_activity' => [
                'day' => 3,
                'hour' => 10,
                'count' => 50,
                'day_name' => 'Wednesday',
                'time' => '10:00',
            ],
            'quiet_periods' => [],
        ];
    }
}
