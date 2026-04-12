# Monitoring & Inspection Tools

DigiPulse includes several tools to help you monitor the system's state, debug issues, and inspect real-time events.

## 1. Laravel Telescope

Telescope provides a beautiful dashboard for debugging your Laravel application. It records all requests, commands, Redis interactions, and more.

* **Local URL**: [http://localhost/telescope](http://localhost/telescope)
* **Purpose**: Inspecting commands sent from Laravel to Redis, incoming API requests (results from Go), and application logs.
* **Documentation**: [Laravel Telescope Docs](https://laravel.com/docs/telescope)

## 2. Redis Key Browser (Queue Mode)

We use **Redis Lists** as a mission-critical job queue. Tasks are added to the queue by Laravel and popped by Go workers.

* **Local URL**: [http://localhost:8001](http://localhost:8001)
* **Key Name**: `laravel_database_monitoring:tasks`
* **Purpose**: Inspecting pending tasks and verifying connection strings.

### How to Monitor Tasks (Queue)

Since tasks are processed almost instantly by the Go worker, the queue should ideally be empty or have very few items. To see activity:

1. Open RedisInsight and go to the **Key Browser**.
2. Find the key `laravel_database_monitoring:tasks`.
3. If the Go worker is stopped, you will see tasks accumulating as JSON strings.
4. If the Go worker is running, you might see the key briefly appearing/disappearing or staying at 0 length.

### Advanced: Redis CLI

To check the number of pending tasks:

```bash
LLEN laravel_database_monitoring:tasks
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

## 3. Laravel Scheduler & Queue

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
   * **Go Logs**: Should say `Successfully reported result`.
   * **Telescope -> Requests**: Should show a `POST /api/internal/results` with a `200` status.
5. **Database**: Check the **Sites Dashboard** in the browser to see the updated status.
