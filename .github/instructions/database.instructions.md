---
applyTo: '**'
---

# Database Standards

Use MySQL best practices.

## Queries

Always:

- Use prepared statements
- Avoid SELECT \*
- Select required columns only

## Transactions

Wrap related operations inside transactions.

## Indexes

Use indexes for:

- Foreign Keys
- Search columns
- JOIN columns

## Performance

Avoid:

- N+1 queries
- Multiple unnecessary queries
- Duplicate queries

## Naming

Tables:

snake_case

Columns:

snake_case

Primary Key:

id

Foreign Key:

user_id

created_at

updated_at

deleted_at

## Soft Delete

Prefer soft delete when business requires audit history.
