---
applyTo: '**'
---

# Project Architecture

Follow Clean Architecture principles.

## Controllers

Controllers should:

- Receive requests
- Validate requests
- Call Services
- Return responses

Controllers must never contain business logic.

## Services

Services contain:

- Business logic
- Validation rules
- Workflow

Services must not execute SQL.

## Repository

Repositories:

- Execute database queries
- Return entities
- Never contain business rules

## Models

Models represent data only.

No business logic.

## Helpers

Helpers should only contain pure utility functions.

## Dependency Injection

Always inject dependencies.

Never instantiate repositories or services inside another class.

Example:

Good

UserService
↓

UserRepository

Bad

new UserRepository()

inside UserService.
