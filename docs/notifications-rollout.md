# Notifiche e SSO – Piano Operativo

Questo checklist ti accompagna nell'attivazione graduale delle nuove funzionalità (monitoraggio stock con webhook reali, sistema notifiche e gestione SSO).

## 1. Preparazione ambiente
1. **Backup DB**: `mysqldump u427445037_coresuiteexp > backup.sql`
2. **Aggiorna repository**: `git pull origin main`
3. **Verifica dipendenze**: `composer install --no-dev --prefer-dist`

## 2. Variabili `.env`
Compila i seguenti campi (usa valori reali per il tuo ambiente):
```
NOTIFICATIONS_WEBHOOK_URL="https://hooks.mycrm.tld/coresuite-stock https://hooks.ops.tld/alert"
NOTIFICATIONS_WEBHOOK_HEADERS='{"Authorization":"Bearer <token>","X-App":"Coresuite"}'
NOTIFICATIONS_QUEUE_DSN=amqp://user:pass@rabbitmq:5672/core
NOTIFICATIONS_QUEUE_EXCHANGE=coresuite.notifications
NOTIFICATIONS_QUEUE_ROUTING_KEY=event.stock
NOTIFICATIONS_QUEUE_NAME=coresuite.stock
```
> Puoi lasciare vuoti header o queue se non servono; basta almeno un URL per avere notifiche immediate.

## 3. Migrazioni SQL
Esegui nell'ordine:
```
mysql -h 193.203.168.205 -u u427445037_coresuiteexp -p u427445037_coresuiteexp < migrations/20251102_add_system_notifications.sql
mysql -h 193.203.168.205 -u u427445037_coresuiteexp -p u427445037_coresuiteexp < migrations/20251107_add_internal_sso.sql
```
Se hai una toolchain personalizzata (es. `php scripts/import_schema.php`), usala in alternativa purché applichi gli stessi file.

## 4. Cron monitoraggio stock
Aggiungi al server applicativo (utente web):
```
*/15 * * * * /usr/bin/php /var/www/coresuite/scripts/monitor_stock.php >> /var/www/coresuite/storage/logs/stock_alerts.log 2>&1
```
- Verifica che `storage/logs/stock_alerts.log` e `storage/logs/notifications.log` siano scrivibili.
- In ambienti multi-node, esegui il cron su un solo nodo o usa lockfile.

## 5. Test funzionali
1. **Stock**: modifica temporaneamente la soglia di un provider per forzare un alert (`UPDATE providers SET reorder_threshold=9999 WHERE id=1;`). Esegui manualmente `php scripts/monitor_stock.php` e verifica:
   - Output CLI riporta alert creati/aggiornati.
   - Notifica appare nella topbar dell'app (pagina `dashboard`).
   - Webhook riceve il payload (controlla log esterno o `storage/logs/notifications.log`).
2. **SSO**:
   - Imposta `SSO_SHARED_SECRET` reale.
   - Visita `index.php?page=sso_authorize` autenticato e valida che il flusso restituisca code/token.
   - Crea un client tramite UI o CLI e prova lo scambio token (`page=sso_token`).
3. **Portal**: apri `public/portal/` e assicurati che feed notifiche funzioni (se abilitato).

## 6. Monitoraggio
- Configura un alert su errori che compaiono in `storage/logs/notifications.log`/`stock_alerts.log`.
- Se usi AMQP, aggiungi health-check sulla coda (`rabbitmqctl list_queues`).
- Pianifica pulizia vecchie notifiche (job mensile: `DELETE FROM system_notifications WHERE created_at < NOW() - INTERVAL 90 DAY`).

## 7. Checklist finale
- [ ] `.env` aggiornato e commit sicuro (senza credenziali sensibili).
- [ ] Migrazioni applicate senza errori.
- [ ] Cron operativo.
- [ ] Test manuali completati (stock alert + SSO).
- [ ] Documentazione aggiornata (`docs/completion-plan.md` rimanda a questo file).

Con questo piano puoi partire subito: comincia da `.env`, applica le migrazioni e prova il cron manualmente; se tutto funziona, automatizza via cron e monitora i log.
