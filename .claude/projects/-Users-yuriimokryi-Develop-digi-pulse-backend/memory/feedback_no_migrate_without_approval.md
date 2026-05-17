---
name: feedback-no-migrate-without-approval
description: Never run artisan migrate without explicit user approval
metadata:
  type: feedback
---

Never run `artisan migrate` (or any destructive DB command) without explicit confirmation from the user.

**Why:** User was caught off guard when migrations ran automatically during development — this can affect local DB state unexpectedly.

**How to apply:** After writing a migration, show it to the user and ask "запустити міграцію?" before executing.
