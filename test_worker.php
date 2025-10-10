#!/usr/bin/env php
<?php

/**
 * Test script to manually start the mail scheduler worker
 * Run this from command line: php test_worker.php
 */

echo "======================================\n";
echo "Mail Scheduler Worker Test\n";
echo "======================================\n\n";

$workerPath = __DIR__ . '/app/Workers/MailSchedulerWorker.php';

if (!file_exists($workerPath)) {
    die("ERROR: Worker file not found at: {$workerPath}\n");
}

echo "Worker file found: {$workerPath}\n";
echo "PHP Binary: " . PHP_BINARY . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "OS: " . PHP_OS_FAMILY . "\n\n";

echo "Starting worker...\n";
echo "--------------------------------------\n";

// Execute worker directly in foreground so we see all output
passthru(PHP_BINARY . ' ' . escapeshellarg($workerPath));

echo "\n--------------------------------------\n";
echo "Worker terminated.\n";
