# Monitoring & Inspection Tools

DigiPulse includes several tools to help you monitor the system's state, debug issues, and inspect real-time events.

## 1. Laravel Telescope

Telescope provides a beautiful dashboard for debugging your Laravel application. It records all requests, commands, Redis interactions, and more.

* **Local URL**: [http://localhost/telescope](http://localhost/telescope)
* **Purpose**: Inspecting commands sent from Laravel to Redis, incoming API requests (results from Go), and application logs.
* **Documentation**: [Laravel Telescope Docs](https://laravel.com/docs/telescope)

## 2. RedisInsight (Pub/Sub Mode)

We use **Redis Pub/Sub** for real-time task dispatching. This means tasks do NOT stay in Redis; they are broadcast and processed instantly.

* **Local URL**: [http://localhost:8001](http://localhost:8001)
* **Purpose**: Visualizing live task broadcasts and verifying connectivity.
* **Connection Details**:
  * **Host**: `redis`
  * **Port**: `6379`
  * **Database**: `0`

### How to Monitor Live Tasks (Pub/Sub)

Since tasks are no longer stored as Lists, the standard Key Browser will be empty. To see activity:

1. Open RedisInsight and click the **Pub/Sub** icon in the sidebar.
2. Click **Add Subscription**.
3. Channel name: `laravel_database_monitoring:tasks` (or check `monitoring:tasks`).
4. Click **Subscribe**.
5. When the Laravel scheduler runs, you will see JSON payloads appearing in this window in real-time.

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
