# Integrazione con business.coresuite.it

Questa guida descrive come collegare **Coresuite Express** all'ERP **business.coresuite.it** utilizzando il connettore introdotto in `IntegrationService`. Segui i passaggi nell'ordine indicato: prima prepara l'integrazione lato ERP, poi completa la configurazione nell'app Express e infine verifica il flusso dati.

---

## 1. Prerequisiti

- Account **amministratore** su business.coresuite.it.
- Ambiente Coresuite Express aggiornato al codice che include `IntegrationService` (branch `main`, commit > integrazione esterna).
- Accesso al server dove gira Express per modificare il file `.env` e leggere i log.
- Database aggiornato con le ultime migrazioni.

---

## 2. Configurazione su business.coresuite.it

1. Accedi al portale ERP con credenziali amministrative.
2. Vai in **Impostazioni → Integrazioni/API** (il nome può variare a seconda della versione).
3. Crea una **nuova applicazione** denominata, ad esempio, `coresuite-express`.
4. Imposta i permessi minimi necessari:
   - Lettura/Scrittura su anagrafiche clienti.
   - Lettura/Scrittura su catalogo prodotti e stock.
   - Creazione ordini/vendite.
   - Facoltativo: permessi per scrivere log o note interne.
5. Salva e recupera i valori forniti dalla piattaforma:
   - **API Key** (obbligatoria) → da copiare in `CORESUITE_API_KEY`.
   - **Webhook secret / firma** (se disponibile) → da copiare in `CORESUITE_WEBHOOK_SECRET`.
   - **Tenant / Organization ID** (se previsto) → da copiare in `CORESUITE_TENANT`.
6. Se la console ERP richiede URL di callback per ricevere webhook, inserisci: `https://<dominio-express>/public/index.php?page=notifications_stream` oppure un endpoint custom se ne prevedi uno dedicato.
7. Prendi nota eventuale dei **rate limit** o delle finestre di autenticazione (alcuni ambienti rigenerano le chiavi periodicamente).

> 💡 **Suggerimento:** crea un utente di servizio dedicato all'integrazione così da poter disattivare/ruotare credenziali senza impattare gli utenti umani.

---

## 3. Configurazione in Coresuite Express

1. Apri il file `.env` e popola le variabili della sezione integrazioni:

   ```ini
   CORESUITE_BASE_URL=https://business.coresuite.it
   CORESUITE_API_KEY=la_tua_api_key_generata
   CORESUITE_TENANT=tenant_o_organizzazione
   CORESUITE_WEBHOOK_SECRET=secret_opzionale
   CORESUITE_ENDPOINTS=
   ```

2. Lascia `CORESUITE_ENDPOINTS` vuoto per usare i percorsi di default del connettore oppure inserisci una mappa JSON per personalizzarli, ad esempio:

   ```ini
   CORESUITE_ENDPOINTS={"customers":"/api/customers","products":"/api/products"}
   ```

3. Salva il file, quindi **riavvia PHP-FPM / il web server** per caricare le nuove variabili (oppure svuota l'OPcache se attivo).
4. Verifica che `storage/logs/integrations.log` sia scrivibile dall'utente del web server.

---

## 4. Endpoint utilizzati da Coresuite Express

| Operazione | Metodo | Endpoint predefinito | Note |
|------------|--------|----------------------|------|
| Upsert cliente | `PUT` | `/api/integrations/customers` | Payload con `external_id`, anagrafica e contatti del cliente. |
| Cancellazione cliente | `DELETE` | `/api/integrations/customers/{id}` | `{id}` è l'`external_id` (es. `customer-123`). |
| Upsert prodotto | `PUT` | `/api/integrations/products` | Include prezzo, stock, aliquote IVA e SKU. |
| Invio vendita | `POST` | `/api/integrations/sales` | Invio scontrino completo con righe e totali. |
| Movimenti magazzino | `POST` | `/api/integrations/inventory` | Utilizzato per allineamenti stock manuali. |

Puoi sovrascrivere ciascun percorso via `CORESUITE_ENDPOINTS` passando un oggetto JSON con le chiavi `customers`, `customer_delete`, `products`, `sales`, `inventory`.

---

## 5. Struttura dei payload

### Cliente
```json
{
  "external_id": "customer-42",
  "full_name": "Mario Rossi",
  "email": "mario.rossi@example.com",
  "phone": "+39061234567",
  "tax_code": "RSSMRA80A01H501Z",
  "note": "Cliente business",
  "synced_at": "2025-11-01T10:24:15+01:00"
}
```

### Prodotto
```json
{
  "external_id": "product-15",
  "name": "iPhone 16 Pro",
  "sku": "IP16PRO-256-SILVER",
  "imei": null,
  "category": "Smartphone",
  "price": 1499.99,
  "stock_quantity": 8,
  "tax_rate": 22.0,
  "vat_code": "IVA22",
  "is_active": true,
  "synced_at": "2025-11-01T10:24:15+01:00"
}
```

### Vendita
```json
{
  "external_id": "sale-104",
  "customer_external_id": "customer-42",
  "customer_name": "Mario Rossi",
  "total": 1599.99,
  "total_paid": 1599.99,
  "balance_due": 0.0,
  "payment_status": "Paid",
  "due_date": null,
  "vat_rate": 22.0,
  "vat_amount": 288.52,
  "discount": 50.0,
  "items": [
    {
      "description": "iPhone 16 Pro 256GB Silver",
      "quantity": 1,
      "unit_price": 1499.99,
      "tax_rate": 22.0,
      "tax_amount": 270.90,
      "product_external_id": "product-15",
      "iccid_code": null
    }
  ],
  "synced_at": "2025-11-01T10:32:41+01:00"
}
```

Assicurati che il team ERP sappia interpretare `external_id`: la parte numerica corrisponde all'ID locale (clienti, prodotti, vendite) in Express.

---

## 6. Flusso di sincronizzazione

1. **Clienti**: ogni creazione/aggiornamento/eliminazione cliente in Express genera una chiamata verso l'ERP.
2. **Prodotti**: `ProductService` invia sync dopo creazioni, aggiornamenti, variazioni fiscali.
3. **Vendite**: al salvataggio della vendita viene inviato il documento; in caso di cancellazioni o resi viene reinviata la versione aggiornata.
4. **Magazzino**: movimenti espliciti (es. aggiustamenti stock) generano payload `inventory`.

Se l'ERP espone webhook di ritorno, valuta di implementarli per mantenere la sincronizzazione bidirezionale (non coperto da questa guida).

---

## 7. Verifica e troubleshooting

1. Abilita il log in `storage/logs/integrations.log`. Dopo ogni operazione dovresti vedere righe tipo:
   ```text
   2025-11-01T10:25:01+01:00 | PUT https://business.coresuite.it/api/integrations/customers | 200
   ```
2. In caso di errore, la riga includerà `error=...`. Condividi il messaggio con il team ERP per identificare problemi di autenticazione/percorso.
3. Se le chiamate non arrivano, controlla che:
   - le variabili `.env` siano corrette e applicate;
   - il server possa raggiungere l'URL ERP (firewall, DNS);
   - non ci siano errori PHP (consulta `storage/logs` e il web server).

---

## 8. Checklist finale

- [ ] Credenziali generate e salvate in modo sicuro.  
- [ ] Variabili `.env` popolate e servizio riavviato.  
- [ ] Primo cliente sincronizzato con esito `200 OK`.  
- [ ] Verifica manuale nel catalogo ERP della vendita di prova.  
- [ ] Piano di rotazione credenziali e gestione degli errori condiviso con il team.

Quando tutti i passi sono completi puoi considerare l'integrazione in produzione. Per domande o problemi aperti, raccogli il contenuto del log `integrations.log` e la risposta HTTP dell'ERP: ci serviranno per diagnosticare insieme eventuali errori.
