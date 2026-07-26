---
applyTo: '**'
---

# Performance Guidelines

Always write efficient code.

## Database

Avoid:

- SELECT \*
- N+1 Queries
- Unnecessary JOINs

## Loops

Avoid nested loops whenever possible.

## Memory

Do not load unnecessary data.

Use pagination.

## Caching

Cache:

- Configuration
- Expensive calculations
- Frequently used lookup data

## Complexity

Prefer

O(n)

over

O(n²)

where possible.
