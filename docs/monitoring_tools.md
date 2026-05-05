# Monitoring & Inspection Tools

DigiPulse includes several tools to help you monitor the system's state, debug issues, and inspect real-time events.

## 1. Laravel Telescope

Telescope provides a beautiful dashboard for debugging your Laravel application. It records all requests, commands, Redis interactions, and more.

* **Local URL**: [http://localhost/telescope](http://localhost/telescope)
* **Purpose**: Inspecting commands sent from Laravel to Redis, incoming API requests (results from Go), and application logs.
* **Documentation**: [Laravel Telescope Docs](https://laravel.com/docs/telescope)

## 2. Redis Key Browser (Queue Mode)

We use **Redis Lists** as mission-critical queues:
- `laravel_database_monitoring:tasks` for scheduled checks (Laravel -> Go worker)
- `monitoring:results` for check results (Go -> Laravel consumer)

* **Local URL**: [http://localhost:8001](http://localhost:8001)
* **Key Names**: `laravel_database_monitoring:tasks`, `monitoring:results`
* **Purpose**: Inspecting pending tasks, result ingestion, and verifying connection strings.

### How to Monitor Tasks and Results (Queue)

Since queues are processed quickly, they should ideally be empty or low. To see activity:

1. Open RedisInsight and go to the **Key Browser**.
2. Find the keys `laravel_database_monitoring:tasks` and `monitoring:results`.
3. If the Go worker is stopped, `laravel_database_monitoring:tasks` will accumulate check tasks.
4. If the Laravel results consumer is stopped, `monitoring:results` will accumulate result payloads.

### Advanced: Redis CLI

To check the number of pending items:

```bash
LLEN laravel_database_monitoring:tasks
LLEN monitoring:results
```

### Advanced: Redis Workbench (CLI)

If you prefer the command line within RedisInsight:

1. Open **Workbench**.
2. Run the command to subscribe to all events:

   ```bash
   PSUBSCRIBE *
   ```

3. To trigger immediate activity, run the scheduler manually in your terminal:

   ```bash
   ./vendor/bin/sail artisan app:schedule-checks
   ```

### Verifying Connection

To test if Laravel can talk to Redis at all:

1. Run this command:

   ```bash
   ./vendor/bin/sail artisan tinker --execute="\Illuminate\Support\Facades\Redis::set('test_connection', 'hello')"
   ```

2. Check the **Key Browser** in RedisInsight. If you see `test_connection`, the connection is working.

## 3. Laravel Scheduler & Workers

### Scheduler (Critical for Monitoring)

The scheduler triggers the site checks every minute. In production, this is handled by CRON, but in development, you should run:

```bash
./vendor/bin/sail artisan schedule:work
```

### Queue Worker

While real-time monitoring results are dispatched via Pub/Sub, other background tasks (like email alerts) use the standard Laravel Queue:

```bash
./vendor/bin/sail artisan queue:work
```

* **Purpose**: `schedule:work` triggers tasks, `queue:work` processes background jobs.
* **Telescope Connection**: Monitor both in **Telescope -> Commands** and **Telescope -> Jobs**.

### Monitor Results Consumer (New)

The monitor service can push check results into Redis (`monitoring:results`) and Laravel consumes them via a dedicated command.

Run this in a separate terminal locally:

```bash
./vendor/bin/sail artisan app:consume-monitor-results
```

Useful debug mode (process a single message and exit):

```bash
./vendor/bin/sail artisan app:consume-monitor-results --once
```

Main env variables:
- `MONITOR_RESULTS_CONSUMER_ENABLED=true`
- `MONITOR_RESULTS_QUEUE=monitoring:results`
- `MONITOR_RESULTS_CONSUMER_BLOCK_SECONDS=5`

## 4. Monitor Logs (Go)

The Go-based monitor service logs its activity to the Docker stdout.

* **Command**: `vendor/bin/sail logs -f monitor`
* **Purpose**: Seeing real-time task reception, execution, and reporting.

---

### How to use these together for debugging (The Flow)

1. **Laravel Schedule**: Check **Telescope -> Commands** to see if `app:schedule-checks` executed.
2. **Redis Broadcast**: Use **RedisInsight -> Pub/Sub** to see if the message was published.
3. **Go Worker**: Check **Worker Logs** to see if it received the message.
4. **Result Reporting**:
   * **Redis**: `monitoring:results` should not accumulate when consumer is running.
   * **Consumer logs**: Should show no validation/processing errors.
5. **Database**: Check the **Sites Dashboard** in the browser to see the updated status.
