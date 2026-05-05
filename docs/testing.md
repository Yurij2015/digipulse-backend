# Testing

The project uses **Pest PHP** for testing. All tests are located in the `tests/` directory.

## Quick Start

To run all tests via Laravel Sail:

```bash
vendor/bin/sail artisan test
```

To run with compact output:

```bash
vendor/bin/sail artisan test --compact
```

## Environment Configuration

A separate environment configuration is used for testing to avoid affecting the local database and cache.

### 1. Local Environment (Sail)
When running tests, Laravel automatically loads the `.env.testing` file if it exists. This is the framework's standard behavior.

The `.env.testing` file is configured to use an isolated database:
- **DB_CONNECTION**: `pgsql`
- **DB_DATABASE**: `testing`
- **CACHE_STORE**: `array` (for speed)
- **SESSION_DRIVER**: `array`

### 2. CI/CD and External Environments
When deploying in CI (e.g., GitHub Actions), ensure that a database with the name specified in `DB_DATABASE` has been created.

If you want to run tests against a different environment file, use the `--env` flag:
```bash
vendor/bin/sail artisan test --env=staging
```

## Working with the Database

For tests that interact with the database, use the `RefreshDatabase` or `LazyRefreshDatabase` trait (in Pest, this is typically done via `uses(RefreshDatabase::class)`).

To manually clear and prepare the database for tests:
```bash
vendor/bin/sail artisan migrate:fresh --env=testing
```

## Useful Pest Commands

Pest provides powerful tools for development:

- **Parallel Testing** (significantly speeds up execution):
  ```bash
  vendor/bin/sail artisan test --parallel
  ```
- **Code Coverage**:
  ```bash
  vendor/bin/sail artisan test --coverage
  ```
- **Filter Tests** (e.g., only Auth):
  ```bash
  vendor/bin/sail artisan test --filter=Auth
  ```
- **Run Only Failed Tests**:
  ```bash
  vendor/bin/sail artisan test --only-failures
  ```
- **Bail** (stop after the first failure):
  ```bash
  vendor/bin/sail artisan test --bail
  ```

## Creating New Tests

Always prefer Feature tests for verifying API endpoints.

```bash
# Feature test
vendor/bin/sail artisan make:test Api/UserTest --pest

# Unit test
vendor/bin/sail artisan make:test Services/MyServiceTest --unit --pest
```
