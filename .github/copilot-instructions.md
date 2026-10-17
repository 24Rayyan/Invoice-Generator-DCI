# Project Guidelines

## Database Safety

- Never run or recommend `php artisan migrate:fresh` in this project; it drops all database tables and data.
- Never invoke `migrate:fresh` indirectly through test runners or other commands; avoid tests that use `RefreshDatabase` when they trigger it.
- For database schema changes, prefer additive Laravel migrations and use `php artisan migrate` to apply them without dropping existing data.
