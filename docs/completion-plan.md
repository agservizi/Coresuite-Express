# Completion Plan

## 1. Foundation
- Document environment variables and ship `.env.example`.
- Align `README.md` with the new bootstrap process (artisan-style installer + .env loader already in place).
- Configure coding standards: PHP-CS-Fixer (PSR-12) and PHPStan (level 6+) with Composer scripts.

## 2. Security Hardening
- [x] Implement CSRF protection for ogni form e endpoint AJAX.
- [x] Aggiungere rate limiting + lockout policy a `AuthController` (per IP + per username).
- [x] Completare il flusso MFA (QR provisioning + TOTP) e forzarlo per i ruoli privilegiati.
- [x] Centralizzare sanitizzazione e validazione input tramite helper condivisi.

## 3. Application Features
- Finalise Customer Portal (self-service requests, privacy consents, notification preferences).
- Implement Discount Campaign management UI/logic tied to `discount_campaigns` tables.
- Complete product inventory with stock alerts, PDA imports, VAT codes, system notifications.

## 4. Observability & Ops
- Introduce structured logging (Monolog) with daily rotation under `storage/logs`.
- Emit domain events (sales, stock alerts) to webhook/queue with retry + dead-letter.
- Implement health checks and job monitoring for cron/scripts.

## 5. Quality & Delivery
- Set up PHPUnit with coverage gate, Pest optional.
- Add feature tests for Auth, ICCID import, Sales, Portal flows.
- Create GitHub Actions workflow (lint, static analysis, tests, build artefact).
- Provide deployment checklist and rollback strategy in `docs/operations.md`.
