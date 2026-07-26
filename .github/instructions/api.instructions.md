---
applyTo: '**/*Controller.php'
---

# API Standards

## Response Format

Always return JSON.

Success

{
"success": true,
"message": "",
"data": {}
}

Error

{
"success": false,
"message": "",
"errors": []
}

## HTTP Status

Use correct status codes.

200 OK

201 Created

204 No Content

400 Bad Request

401 Unauthorized

403 Forbidden

404 Not Found

422 Validation Error

500 Internal Server Error

## Controllers

Controllers should:

- Validate request
- Call service
- Return response

No business logic.

## Validation

Return meaningful validation errors.

## Pagination

Always paginate large collections.
