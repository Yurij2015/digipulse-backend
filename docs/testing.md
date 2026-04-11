# Тестування (Testing)

Проєкт використовує **Pest PHP** для тестування. Всі тести знаходяться в директорії `tests/`.

## Швидкий старт

Для запуску всіх тестів через Laravel Sail:

```bash
vendor/bin/sail artisan test
```

Для запуску з компактним виводом:

```bash
vendor/bin/sail artisan test --compact
```

## Конфігурація оточень

Для тестування використовується окрема конфігурація середовища, щоб не зачіпати локальну базу даних та кеш.

### 1. Локальне середовище (Sail)
При запуску тестів Laravel автоматично завантажує файл `.env.testing`, якщо він існує. Це стандартна поведінка фреймворку.

Файл `.env.testing` налаштований на використання ізольованої бази даних:
- **DB_CONNECTION**: `pgsql`
- **DB_DATABASE**: `testing`
- **CACHE_STORE**: `array` (для швидкості)
- **SESSION_DRIVER**: `array`

### 2. CI/CD та зовнішні оточення
При розгортанні в CI (наприклад, GitHub Actions) необхідно переконатися, що створена база даних з іменем, вказаним у `DB_DATABASE`. 

Якщо ви хочете запустити тести проти іншого файлу оточення, використовуйте префікс `APP_ENV`:
```bash
vendor/bin/sail artisan test --env=staging
```

## Робота з базою даних

Для тестів, що взаємодіють з БД, використовуйте трейт `RefreshDatabase` або `LazyRefreshDatabase` (у Pest це зазвичай робиться через `uses(RefreshDatabase::class)`).

Щоб вручну очистити та підготувати базу для тестів:
```bash
vendor/bin/sail artisan migrate:fresh --env=testing
```

## Корисні команди Pest

Pest надає потужні інструменти для розробки:

- **Паралельне тестування** (значно пришвидшує проходження):
  ```bash
  vendor/bin/sail artisan test --parallel
  ```
- **Покриття коду (Coverage)**:
  ```bash
  vendor/bin/sail artisan test --coverage
  ```
- **Фільтрація тестів** (наприклад, лише Auth):
  ```bash
  vendor/bin/sail artisan test --filter=Auth
  ```
- **Запуск лише провалених тестів**:
  ```bash
  vendor/bin/sail artisan test --only-failures
  ```
- **Bail** (зупинка після першої помилки):
  ```bash
  vendor/bin/sail artisan test --bail
  ```

## Створення нових тестів

Завжди віддавайте перевагу Feature-тестам для перевірки API.

```bash
# Feature test
vendor/bin/sail artisan make:test Api/UserTest --pest

# Unit test
vendor/bin/sail artisan make:test Services/MyServiceTest --unit --pest
```
