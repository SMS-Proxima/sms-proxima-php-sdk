# SMS Proxima PHP SDK

SDK PHP officiel pour l'[API SMS Proxima](https://sms-proxima.com/api-sms).

## Installation

```bash
composer require sms-proxima/sdk
```

**Prérequis :** PHP >= 7.4, extensions `curl` et `json`.

---

## Démarrage rapide

```php
use SmsProxima\SmsProxima;

$sms = new SmsProxima('VOTRE_TOKEN');

// Envoyer un SMS
$result = $sms->send('33612345678', 'BOUTIQUE', 'Votre commande est confirmée.');

echo $result['ticket'];  // api-42-1712345678
echo $result['credits']; // crédits restants
```

---

## Utilisation

### Test de connexion

```php
$response = $sms->ping();
// ['message' => 'Authentifié avec succès', 'user' => [...]]
```

### Crédits disponibles

```php
$credits = $sms->credits(); // int
```

### Envoi SMS

```php
// Envoi simple
$sms->send('33612345678', 'EXPEDITEUR', 'Votre message');

// Envoi multiple
$sms->send(['33612345678', '33687654321'], 'EXPEDITEUR', 'Votre message');

// Avec options
$sms->send('33612345678', 'EXPEDITEUR', 'Votre message', [
    'stop'           => 1,              // Mention STOP (défaut : 1)
    'timeToSend'     => '2026-03-15 10:00', // Envoi programmé
    'sandbox'        => 1,              // Mode test (aucun SMS envoyé, aucun crédit débité)
    'idempotencyKey' => 'uuid-v4-ici',  // Anti double-envoi
]);
```

### Comptage de caractères

```php
$count = $sms->count('Mon message');
// ['nb_sms' => 1, 'nb_caracteres' => 147]
```

### Historique des campagnes

```php
$campaigns = $sms->campaigns();
$campaigns = $sms->campaigns(2); // page 2
```

### Accusés de réception par destinataire

```php
$deliveries = $sms->deliveries('api-42-1712345678');
// Retourne la liste paginée des AR reçus pour ce ticket
```

### Blacklist

```php
// Lister
$list = $sms->getBlacklist();

// Ajouter
$sms->addToBlacklist('33612345678');

// Supprimer (impossible si le numéro a répondu STOP)
$sms->removeFromBlacklist('33612345678');
```

---

## Gestion des erreurs

```php
use SmsProxima\SmsProxima;
use SmsProxima\Exceptions\AuthenticationException;
use SmsProxima\Exceptions\InsufficientCreditsException;
use SmsProxima\Exceptions\ValidationException;
use SmsProxima\Exceptions\SmsProximaException;

try {
    $result = $sms->send('33612345678', 'BOUTIQUE', 'Votre commande est confirmée.');

} catch (InsufficientCreditsException $e) {
    echo 'Crédits insuffisants. Disponibles : ' . $e->getAvailableCredits();
    echo ' / Requis : ' . $e->getRequiredCredits();

} catch (AuthenticationException $e) {
    echo 'Clé API invalide ou compte non validé.';

} catch (ValidationException $e) {
    echo 'Erreur de validation : ' . $e->getMessage();
    print_r($e->getErrors());

} catch (SmsProximaException $e) {
    echo 'Erreur API : ' . $e->getMessage();
    echo ' (code : ' . $e->getApiCode() . ')';
}
```

---

## Webhook — accusés de réception

Configurez votre URL webhook depuis votre [espace API](https://sms-proxima.com/dashboard/api).
SMS Proxima enverra un `POST JSON` sur votre endpoint à chaque accusé de réception.

**Exemple de payload reçu :**

```json
{
  "version": "v1",
  "event": "delivery.receipt",
  "event_id": "1711175235",
  "emitted_at": "2026-03-01T10:00:00+00:00",
  "campaign_id": 1234,
  "message": {
    "to": "33612345678"
  },
  "delivery": {
    "status": "delivered",
    "status_code": "0",
    "error_code": "000",
    "received_at": "2026-03-01T09:59:55+00:00"
  }
}
```

**Votre endpoint doit répondre HTTP 2xx dans les 6 secondes.**
En cas d'échec, la requête est rejouée jusqu'à 8 fois sur 24h.
Utilisez `event_id` pour détecter les doublons éventuels.

---

## Licence

MIT — voir [LICENSE](LICENSE)
