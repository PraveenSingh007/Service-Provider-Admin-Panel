---
applyTo: '**'
---

# Testing Standards

Use PHPUnit.

Generate tests for:

- Happy path
- Validation failure
- Exceptions
- Edge cases

## Test Naming

Example

test_user_can_login()

test_invalid_email_returns_error()

## Mocks

Mock:

- Repository
- External API
- Email
- SMS

Do not mock value objects.

## Coverage

Aim for:

80%+

business logic coverage.
