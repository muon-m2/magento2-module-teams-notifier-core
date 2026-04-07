# Changelog

All notable changes to `Muon_TeamsNotifierCore` are documented in this file.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Versioning follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- `TeamsNotifierInterface` with `send()` (named channel) and `sendToWebhook()` (ad-hoc URL) methods.
- `AdaptiveCardMessageInterface` / `AdaptiveCardMessage` — the only message format supported by Teams Workflows webhooks.
- `ChannelRepositoryInterface` / `ChannelRepository` for full CRUD on named channels.
- `Channel` ORM model backed by `muon_teamsnotifiercore_channel` table; webhook URL and TriggerSecret stored encrypted at rest via `EncryptorInterface`.
- Per-channel `trigger_secret` field sent as the `TriggerSecret` HTTP header for Power Automate flow authentication.
- Admin CRUD UI under **Stores → Teams Notifier → Channels** (UiComponent grid + form).
- **Adaptive Card templates** — `TemplateRepositoryInterface` / `TemplateRepository` and `Template` ORM model backed by `muon_teamsnotifiercore_template` table.
- Admin CRUD UI under **Stores → Teams Notifier → Templates** (UiComponent grid + form).
- Per-channel template assignment: channels have a `template_id` FK (`ON DELETE SET NULL`); the assigned template overrides the caller-supplied card body at delivery time.
- `TemplateVariableSubstitutor` — server-side `${variable}` placeholder resolution in template JSON, applied at send/consume time from data passed to `send()`.
- `Template\JsonValidator` — validates Adaptive Card JSON structure (`type`, `body`, `version`) before save.
- `send()` and `sendToWebhook()` accept an optional `array $data` parameter for template variable substitution.
- Async consumer applies template substitution at consume time, so template edits between enqueue and delivery are reflected in the final card.
- Unit tests: `TemplateVariableSubstitutorTest`, `JsonValidatorTest`, `TemplateRepositoryTest`; extended `TeamsNotifierTest` and `ConsumerTest`.
- Async delivery mode via RabbitMQ (`muon.teams_notifier_core.send` topic).
- Exponential-backoff retry in `Queue\Consumer`: configurable max attempts, base delay, multiplier.
- `muon_teams_notifier_core_delivery_failed` Magento event dispatched after all retries are exhausted.
- Admin configuration section `muon_teamsnotifiercore` covering general settings and queue/retry tuning.
- `i18n/en_US.csv` with all user-facing strings.

### Notes
- Targets **Teams Workflows webhooks** (`*.aa.environment.api.powerplatform.com`) exclusively. Office 365 Connectors (`webhook.office.com`) were retired by Microsoft on April 30 2026 and are not supported.
- Template `${variable}` syntax matches the placeholder format used by the Microsoft design service that generates Adaptive Card templates.
