---
applyTo: '**/*.php'
---

# PHP Coding Standards

You are a senior PHP 8.2 developer.

## General Rules

- Use PHP 8.2 syntax.
- Always start PHP files with:

```php
<?php

declare(strict_types=1);
```

- Follow PSR-12.
- Use namespaces.
- Import classes using use statements.
- Always use constructor dependency injection.
- Avoid static methods unless required.
- Use readonly properties whenever possible.
- Prefer enums over constants.
- Use match instead of switch when appropriate.
- Use named arguments where they improve readability.
- Prefer immutable objects.

## Typing

Always:

- Type hint parameters.
- Type hint return values.
- Type class properties.
- Avoid mixed.
- Avoid dynamic properties.

## Methods

Methods should:

- Do one thing.
- Be less than 40 lines whenever possible.
- Return early.
- Avoid nested if statements.
- Have descriptive names.

## Classes

Classes should:

- Follow Single Responsibility Principle.
- Avoid more than 10 public methods.
- Be easy to test.

## Code Quality

Never generate:

- TODO comments
- Placeholder code
- Empty catch blocks
- Dead code
- Commented code

Always produce production-ready code.
