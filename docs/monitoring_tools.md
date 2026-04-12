# Monitoring & Inspection Tools

DigiPulse includes several tools to help you monitor the system's state, debug issues, and inspect Redis queues.

## 1. Laravel Telescope

Telescope provides a beautiful dashboard for debugging your Laravel application. It records all requests, commands, Redis interactions, and more.

* **Local URL**: [http://localhost/telescope](http://localhost/telescope)
* **Purpose**: Inspecting commands sent from Laravel to Redis (Scheduler), API requests, and application logs.
* **Documentation**: [Laravel Telescope Docs](https://laravel.com/docs/telescope)

## 2. RedisInsight

RedisInsight is a powerful visualization tool for managing and optimizing data in Redis.

* **Local URL**: [http://localhost:8001](http://localhost:8001)
* **Purpose**: Visualizing the state of Redis keys, monitoring queue lengths, and inspecting task payloads.
* **Connection Details**:
  * **Host**: `redis` (or `digipulse-redis` depending on your compose config)
  * **Port**: `6379`
  * **Database**: `0`
* **Documentation**: [RedisInsight Docs](https://redis.io/docs/latest/develop/tools/insight/)

### Verifying Connection

If you see an empty database, it might be because tasks are processed too fast. To test the connection:

1. Run this command to set a persistent test key:

   ```bash
   ./vendor/bin/sail artisan tinker --execute="\Illuminate\Support\Facades\Redis::set('test_connection', 'hello_from_laravel')"
   ```

2. Click **Refresh** in RedisInsight. You should see the `test_connection` key.

## 3. Monitor Logs (Go)

The Go-based monitor service logs its activity to the Docker stdout.

* **Command**: `vendor/bin/sail logs -f monitor`
* **Purpose**: Seeing real-time task processing and reporting results back to the API.

---

### How to use these together for debugging

1. **Telescope**: Verify that `app:schedule-checks` pushed a command to Redis.
2. **RedisInsight**: Check if the task is sitting in the `laravel-database-monitoring:tasks` list.
3. **Monitor Logs**: Confirm that the worker picked up the task and completed the check.
