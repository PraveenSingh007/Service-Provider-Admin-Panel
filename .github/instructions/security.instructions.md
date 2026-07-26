---
applyTo: '**/*.php'
---

# Security Guidelines

Security is mandatory.

## SQL Injection

Always:

- Use prepared statements.
- Bind parameters.
- Never concatenate SQL.

## XSS

Escape all HTML output.

Use htmlspecialchars() where appropriate.

## CSRF

Protect all POST, PUT, PATCH and DELETE requests.

## Authentication

Passwords:

- password_hash()
- password_verify()

Never use md5() or sha1().

## Secrets

Never hardcode:

- Passwords
- API Keys
- Tokens

Read from environment variables.

## File Uploads

Validate:

- MIME type
- Extension
- Size

Never trust filename.

## Validation

Validate all user input.

Never trust client-side validation.
