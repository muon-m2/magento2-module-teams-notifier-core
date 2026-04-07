# Muon_TeamsNotifierCore

Core service module for sending Microsoft Teams notifications via **Teams Workflows webhooks**. Provides a clean Service Contract that other `Muon_*` modules depend on — they inject `TeamsNotifierInterface` and never implement HTTP-to-Teams logic themselves.

---

## Purpose

Centralises all Teams webhook communication: channel management, payload building, HTTP delivery, and async queue/retry. Consuming modules only need to `require` this package and inject the interface.

---

## Features

- **Adaptive Card format** — the only format supported by current Teams Workflows webhooks (`*.aa.environment.api.powerplatform.com`).
- **Named channels** — manage Workflows webhook URLs in the Magento admin; reference them by slug in code.
- **Adaptive Card templates** — store reusable card designs in the admin; assign a template per channel for consistent branding without code changes.
- **Variable substitution** — runtime `${variable}` placeholders in templates are resolved from data passed to `send()`.
- **TriggerSecret authentication** — optional per-channel secret sent as the `TriggerSecret` HTTP header for Power Automate flow authentication.
- **Per-call webhook override** — `sendToWebhook()` bypasses channel lookup for one-off URLs.
- **Sync / Async delivery modes** — switch between direct HTTP POST and RabbitMQ queue without changing consumer code.
- **Exponential-backoff retry** — configurable max attempts, base delay, multiplier.
- **Failure event** — `muon_teams_notifier_core_delivery_failed` dispatched after all retries are exhausted, so other modules can alert or log.
- **Encrypted storage** — webhook URLs and TriggerSecrets stored encrypted in the database via `EncryptorInterface`.

---

## Installation

1. Place the module in `app/code/Muon/TeamsNotifierCore/`.
2. Enable and set up:
   ```bash
   docker compose exec -u magento php bin/magento module:enable Muon_TeamsNotifierCore
   docker compose exec -u magento php bin/magento setup:upgrade
   docker compose exec -u magento php bin/magento setup:di:compile
   docker compose exec -u magento php bin/magento cache:clean
   ```
3. For async mode, start the queue consumer (typically managed by Supervisor):
   ```bash
   docker compose exec -u magento php bin/magento queue:consumers:start muonTeamsNotifierCoreSend
   ```

---

## Configuration

Navigate to **Stores → Configuration → Muon → Teams Notifier Core**.

| Path | Default | Description |
|---|---|---|
| `general/enabled` | Yes | Master kill-switch. Both send methods silently no-op when disabled. |
| `general/default_channel` | *(empty)* | Slug used when `send()` is called with `null`. |
| `general/timeout` | 10 | cURL timeout (seconds) per HTTP POST. |
| `general/delivery_mode` | sync | `sync` = inline HTTP; `async` = RabbitMQ queue. |
| `queue/max_attempts` | 3 | Total delivery attempts before permanent failure. |
| `queue/retry_delay` | 60 | Base delay (seconds) before attempt 2. |
| `queue/backoff_multiplier` | 2 | Delay multiplier: attempt 2 = 60 s, 3 = 120 s, 4 = 240 s… |

### Creating an Adaptive Card Template

Templates store a full Adaptive Card JSON body in the admin and can be assigned to channels. At send time the module resolves `${variable}` placeholders from the data map passed to `send()`.

1. Go to **Stores → Teams Notifier → Templates → Add New Template**.
2. Enter a **Name** (slug, e.g. `order-alert`) and a **Label** (human-readable display name).
3. Paste the full Adaptive Card JSON into the **Adaptive Card JSON** field.  
   Use `${variable_name}` syntax for runtime substitution, for example:
   ```json
   {
     "type": "AdaptiveCard",
     "version": "1.5",
     "body": [
       { "type": "TextBlock", "text": "Order ${order_id} placed by ${customer}", "wrap": true }
     ]
   }
   ```
4. Save, then open a channel and select the template from the **Adaptive Card Template** dropdown.

> Variables not present in the data map are left as-is in the final card.

#### Adaptive Card resources

| Resource | URL |
|---|---|
| Adaptive Cards Designer (visual builder) | https://adaptivecards.microsoft.com/designer |
| Schema explorer (all element types) | https://adaptivecards.io/explorer/ |
| Official specification | https://adaptivecards.io/documentation/ |
| Adaptive Card Templating overview | https://learn.microsoft.com/en-us/adaptive-cards/templating/ |
| Teams card design guidelines | https://learn.microsoft.com/en-us/microsoftteams/platform/task-modules-and-cards/cards/design-effective-cards |
| Power Automate Teams webhook trigger | https://learn.microsoft.com/en-us/connectors/teams/?tabs=text1%2Cdotnet#microsoft-teams-triggers |

---

### Creating a Channel

Channels use **Teams Workflows webhooks** (Power Automate), not the retired Office 365 Connectors.

1. In Microsoft Teams, open the channel you want to post to.
2. Go to **channel settings → Edit → Connectors** — or use the **Workflows** app directly.
3. In the **Power Automate** portal, create a flow with the trigger **"When a Teams webhook request is received"** (Workflows app). Copy the webhook URL from the trigger step.
4. Optionally copy the **TriggerSecret** from the trigger configuration for authenticated requests.
5. Go to **Stores → Teams Notifier → Channels → Add New Channel**.
6. Paste the Workflows webhook URL, optionally the TriggerSecret, set a slug and label, activate, and save.

> The webhook URL pattern is:
> `https://<env-id>.aa.environment.api.powerplatform.com/powerautomate/automations/…`

---

## Sending Critical Notifications

The predefined `critical-error` channel ships with the `CRITICAL notice` Adaptive Card template.
Before use, open **Stores → Teams Notifier → Channels → CRITICAL Error**, paste the Power Automate
Workflows webhook URL, and set the channel to active.

At runtime, inject `TeamsNotifierInterface` and call `send()` with the three template variables:

```php
use Muon\TeamsNotifierCore\Api\TeamsNotifierInterface;
use Muon\TeamsNotifierCore\Model\AdaptiveCardMessage;

class MyService
{
    public function __construct(
        private readonly TeamsNotifierInterface $teamsNotifier
    ) {
    }

    public function notifyCriticalError(\Throwable $e): void
    {
        $message = (new AdaptiveCardMessage())
            ->setTitle('Critical Error')
            ->setSummary('A critical error has occurred.');

        $this->teamsNotifier->send($message, 'critical-error', [
            'Caption'           => 'Payment gateway unreachable',
            'Short Description' => 'All checkout attempts are failing. Immediate action required.',
            'Full Description'  => sprintf(
                "Exception: %s\n\nFile: %s:%d\n\nTrace:\n%s",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            ),
        ]);
    }
}
```

The `critical-error` channel resolves the three `${…}` placeholders at send time:

| Variable | Card element |
|---|---|
| `Caption` | Large bold heading |
| `Short Description` | Always-visible summary below the heading |
| `Full Description` | Expanded body revealed by the ▼ chevron button |

> In **async** delivery mode `send()` returns immediately and the HTTP POST is handled by the
> queue consumer. No exception is thrown on delivery failure — observe the
> `muon_teams_notifier_core_delivery_failed` event instead.

---

## Public API

### `Api/TeamsNotifierInterface`

```php
// Send to a named channel (null = admin-configured default)
$notifier->send($message, 'ops-alerts');
$notifier->send($message);            // uses default_channel config

// Pass runtime data for ${variable} substitution in the channel's assigned template
$notifier->send($message, 'ops-alerts', [
    'order_id' => '#10042',
    'customer' => 'Alice Smith',
]);

// Send to any Workflows webhook URL, bypassing channel lookup
$notifier->sendToWebhook($message, 'https://<env>.aa.environment.api.powerplatform.com/…');
```

In **async mode** both methods return immediately; HTTP errors are handled by the consumer.
In **sync mode** a `LocalizedException` is thrown on delivery failure.

### `Api/ChannelRepositoryInterface`

Full CRUD for channel entities: `save()`, `getById()`, `getByName()`, `delete()`, `deleteById()`, `getList()`.

### `Api/TemplateRepositoryInterface`

Full CRUD for template entities: `save()`, `getById()`, `getByName()`, `delete()`, `deleteById()`, `getList()`. `save()` validates the JSON against the Adaptive Card schema before persisting.

### Message Value-Object

**AdaptiveCardMessage** — the only supported format:

```php
use Muon\TeamsNotifierCore\Model\AdaptiveCardMessage;

$message = (new AdaptiveCardMessage())
    ->setTitle('Deployment Complete')
    ->setSummary('v2.3.1 deployed to production')
    ->setThemeColor('0078D4')
    ->setCardBody([
        ['type' => 'TextBlock', 'text' => 'Version **2.3.1** is live.', 'wrap' => true],
        ['type' => 'FactSet', 'facts' => [
            ['title' => 'Environment', 'value' => 'Production'],
            ['title' => 'Deployed by', 'value' => 'CI/CD'],
        ]],
    ])
    ->setCardActions([
        ['type' => 'Action.OpenUrl', 'title' => 'View Release', 'url' => 'https://example.com/release'],
    ]);
```

See the [Adaptive Cards schema explorer](https://adaptivecards.io/explorer/) for the full list of body element and action types, and the [Microsoft designer](https://adaptivecards.microsoft.com/designer) to build and preview cards visually.

### Observing Delivery Failures

```php
// etc/events.xml
<event name="muon_teams_notifier_core_delivery_failed">
    <observer name="my_module_teams_failure" instance="My\Module\Observer\TeamsFailureObserver"/>
</event>

// Observer receives: $observer->getData('notification') and $observer->getData('exception')
```

---

## Dependencies

| Module | Reason |
|---|---|
| `magento/framework` | DI, Curl, ScopeConfig, Encryption, MessageQueue, Serialize |
| `magento/module-store` | Store-scope configuration |
| `magento/module-backend` | Admin controllers and menu |
| `magento/module-ui` | UiComponents (grid and form) |
| `magento/module-config` | `Backend\Encrypted` config backend |
| `magento/module-message-queue` | Queue publisher/consumer infrastructure |
| `magento/module-amqp` | AMQP transport (RabbitMQ) |

---

## Known Limitations

- **Only Workflows webhooks are supported.** Office 365 Connectors (`webhook.office.com`) were retired on April 30 2026 and are not supported.
- **Retry delay uses `sleep()`** in the consumer worker (bounded to 30 s). For sub-second precision retry, replace with a RabbitMQ Dead Letter Exchange with a TTL queue.
- **No mass-delete** for channels from the admin grid (mass action placeholder is present in the listing XML; the `massDelete` controller is not yet implemented).
- **Webhook URL and TriggerSecret are cleared** in the edit form for security; users must re-enter them to change the values.
- **Template substitution is flat key→value only.** Nested data structures are not supported; flatten any complex objects before passing to `send()`.
- **Deleting a template** sets `template_id` to `NULL` on all channels that referenced it (via `ON DELETE SET NULL`). Affected channels fall back to the caller-supplied card body.
