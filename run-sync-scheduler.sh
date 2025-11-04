#!/bin/bash
# Cloud Sync Scheduler - Runs Laravel scheduler continuously
# This will execute the sync:cloud command every 10 seconds

echo "========================================"
echo "  Cloud Sync Scheduler - AUTO MODE"
echo "========================================"
echo ""
echo "Started at: $(date)"
echo "Syncing every 10 seconds..."
echo "Press Ctrl+C to stop"
echo "========================================"
echo ""

while true; do
    php artisan schedule:run
    sleep 10
done

