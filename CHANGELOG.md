# Changelog

All notable changes to `yii2-metrika-loader` will be documented in this file.

## [1.2.0] - 2026-10-28

### Added & Improved
- **Interactive Settings Form**: administrators can now change the Yandex Metrika counter number (`counterId`), toggle features (`clickmap`, `webvisor`, `trackLinks`, `accurateTrackBounce`, `enabled`), select loader mode (`deferred` vs `immediate`), and configure `ecommerce` / `allowedHosts` directly from the admin panel.
- **Persistent JSON Storage**: settings are automatically saved to `@common/config/metrika.json` (or `@runtime/metrika.json`) and seamlessly applied to the frontend and backend without touching PHP config files.
- **Cache Invalidation**: automatically flushes application cache on save for instant reflection.
- **Removed Outer Layout Padding**: removed `padding: 20px 16px 40px` from `.ym-dashboard` in `metrika-admin.css` to eliminate unwanted top and side gaps inside admin panel layouts.
- **Graceful Onboarding**: opening the admin dashboard on a fresh install without a configured `counterId` no longer throws a fatal `InvalidConfigException`, allowing immediate configuration via UI.

## [1.1.1] - 2026-10-28

### Changed & Improved
- Simplified and decluttered admin dashboard UI: clear cards for core features with human explanations in 1 sentence.
- Moved fine-tuning speed parameters (timeouts, interaction triggers) into a clean collapsible accordion block.
- Streamlined cheat sheet with 1-click copy buttons for layout widget and JavaScript goal tracking.

## [1.1.0] - 2026-10-28

### Added
- Standalone Yii2 admin module (`larikmc\metrika\Module`) and dashboard controller (`DefaultController`).
- Responsive, zero-dependency admin UI with dark/light theme support (`MetrikaAdminAsset`, `metrika-admin.css`, `metrika-admin.js`).
- Complete settings inspection and audit table with explanations of each parameter, PSI/TBT performance impact, and optimal recommendations.
- Quick copy developer cheatsheet for layout integration and conversion goal tracking (`ym(id, 'reachGoal')`).
- Direct link to Yandex Metrika dashboard for the active counter ID.

## [1.0.0] - 2026-10-27

### Added
- Initial release of Universal Yii2 Metrika Loader.
- Support for `deferred` (high-performance, PageSpeed optimized) and `immediate` modes.
- Early queue `window.ym` to prevent loss of goal conversions (`reachGoal`).
- Backward-compatible proxy for legacy `window.yaCounter<ID>` calls.
- Deferred script loading via double `requestAnimationFrame` and `requestIdleCallback`.
- Immediate loading on first user interaction (`pointerdown`, `touchstart`, `scroll`, `keydown`, `wheel`, `mousemove`).
- Safety fallback timeout (`maxDelay`).
- Script priority optimization (`fetchPriority="low"`, `async=true`).
- Duplicate script injection prevention.
- Cookie/Privacy Consent integration (e.g. `cookie_analytics_enabled`).
- Allowed host filtering.
- Content Security Policy (CSP) nonce support.
- Full PHPUnit test coverage (11 tests, 54 assertions).
- Yii2 component and widget (`MetrikaLoader`, `MetrikaWidget`).
