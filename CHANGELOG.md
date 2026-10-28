# Changelog

All notable changes to `yii2-metrika-loader` will be documented in this file.

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
