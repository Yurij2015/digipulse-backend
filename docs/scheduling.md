# Task Scheduling in DigiPulse

This document explains how the automated monitoring checks are scheduled and executed in the DigiPulse application.

## Overview

The core of the monitoring system relies on a scheduled command that scans for sites due for a check and pushes them to a Redis queue. The Go-based monitor service (now integrated into the main Sail stack) then picks up these tasks.

The main scheduling logic is defined in `routes/console.php`:

```php
Schedule::command('app:schedule-checks')->everyMinute();
```

## Running the Scheduler Locally

When developing locally with **Laravel Sail**, the scheduler does not run automatically. You must manually start a process that simulates the system's cron heartbeat.

### The `schedule:work` Command

The `schedule:work` command runs in the foreground and invokes the scheduler every minute.

**Run this command in a separate terminal window:**

```bash
./vendor/bin/sail artisan schedule:work
```

> [!IMPORTANT]
> If this command is not running, your sites will not be checked automatically, even if they are active.

### The `app:consume-monitor-results` Command

If Go monitor publishes results to Redis, Laravel must run a dedicated consumer process to persist those results.

Run this command in a separate terminal window:

```bash
./vendor/bin/sail artisan app:consume-monitor-results
```

For debugging a single payload:

```bash
./vendor/bin/sail artisan app:consume-monitor-results --once
```

## Manual Execution

If you want to trigger all due checks immediately without waiting for the scheduler, you can run the command directly:

```bash
./vendor/bin/sail artisan app:schedule-checks
```

## Monitoring the Schedule

To see all scheduled tasks and when they are next due to run, use:

```bash
./vendor/bin/sail artisan schedule:list
```

## The Monitor Service (Go Worker)

The Go-based monitor service is integrated into the main `compose.yaml`. This means it starts automatically when you run:

For technical details on how each type of check (HTTP, SSL, etc.) is implemented, see the [Checkers Documentation](checkers.md).

```bash
./vendor/bin/sail up -d
```

### Viewing Monitor Logs

To see what the monitor service is doing in real-time:

```bash
./vendor/bin/sail logs -f monitor
```

## Development & Hot-Reloading (Go)

The monitor service supports **Hot-Reloading** using [Air](https://github.com/air-verse/air), so you don't need to restart Docker when changing Go code.

### How it Works

1. **Shared Volume**: The `../monitor` directory is mounted into the container at `/app`.
2. **Live Watcher**: The container uses `Dockerfile.dev` which runs `air`.
3. **Auto Rebuild**: When you save any `.go` file in the `monitor` directory, the service automatically recompiles and restarts within seconds.

### Troubleshooting Hot-Reload

* **View rebuild status**: Use `./vendor/bin/sail logs -f monitor`. You will see `main.go has changed` and `building...` messages.
* **Dependency changes**: If you add new packages via `go get` or change `go.mod`, you must rebuild the container:

    ```bash
    ./vendor/bin/sail up -d --build monitor
    ```

## Inspecting Redis

If the scheduling command is working but checks aren't being processed, check the state of Redis.

### Checking the Task/Result Queues

To see if there are tasks or results waiting in Redis:

```bash
# Check queue length (should be close to 0 if worker is active)
./vendor/bin/sail exec redis redis-cli LLEN monitoring:tasks
./vendor/bin/sail exec redis redis-cli LLEN monitoring:results

# View all tasks currently in the queue
./vendor/bin/sail exec redis redis-cli LRANGE monitoring:tasks 0 -1
./vendor/bin/sail exec redis redis-cli LRANGE monitoring:results 0 -1
```

## Production Environment

In a production environment, you should not use `schedule:work`. Instead, a standard Cron job should be configured on the server to run every minute:

```cron
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Troubleshooting

* **Checks not triggering:** Ensure `./vendor/bin/sail artisan schedule:work` is running.
* **Queue not processing:** Ensure the `monitor-service` container is running (`sail ps`).
* **Results not appearing in Laravel:** Ensure `./vendor/bin/sail artisan app:consume-monitor-results` is running and check `LLEN monitoring:results`.
* **Check Intervals:** Sites are only picked up if their `last_checked_at` is older than the site's `update_interval`.
