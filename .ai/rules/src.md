---
paths:
  - 'app-modules/**/src/**/*.php'
---

# Src

## No MySQL-only SQL in queries (tests use SQLite)
Tests run on in-memory SQLite, so raw MySQL functions like FIELD() in orderByRaw/query strings fail with "no such function". Restore selection order in PHP (sortBy + array_search) instead of FIELD(id, ...).
