# PathLabCA

Pathology laboratory system for patient orders, specimen barcodes, structured results and versioned PDF reports.

Reference intervals are starter values cited from published sources (ABIM Laboratory Test Reference Ranges, January 2026, and ACCP CCSAP where noted). A laboratory director must confirm them against the local method before clinical use. Demonstration patients are watermarked and cannot be delivered as clinical reports.

## Staff demo sign-in

Password for every account: `password`

- admin@pathlabca.test
- reception@pathlabca.test
- collect@pathlabca.test
- tech@pathlabca.test
- sign@pathlabca.test

## Setup

```bash
composer setup
php artisan db:seed
composer run dev
```

The app is served at http://localhost:8000.
