# About this fork

`LeFuel` is a [Leborn](https://github.com/leborn-dev) fork of [FuelPHP](https://github.com/fuel/fuel).

## Why this fork exists

FuelPHP development effectively halted in 2018, yet several Japanese companies (including Makuake) still rely on it in production. We provide LTS support and a clear migration path to Laravel.

## What we are doing

This fork goes through 5 phases:

- **Phase A: Setup and Analysis** - Docker dev environment, codebase overview, dependency status, compatibility issues
- **Phase B: PHP 8.x compatibility** - Make the codebase run on PHP 8.x
- **Phase C: Modernize core dependencies** - Update EOL dependencies to current versions
- **Phase D: Tests and CI** - Test coverage and matrix CI on GitHub Actions
- **Phase E: AI-native rebirth and v0.1.0 release** - AI-assisted FuelPHP -> Laravel migration assistant. Run lefuel migrate and let AI propose code-level migration patches.

## Status

This is an **early-stage** fork. The repository was initialized on 2026-05-02 with a full mirror of the upstream codebase. Modernization and Leborn-specific features are tracked in [Issues](../../issues).

Estimated duration to v0.1.0: **1 to 2 weeks** (with Claude Code-augmented development).

## Original project

- Name: FuelPHP
- Repository: https://github.com/fuel/fuel
- License: MIT

This fork retains all upstream commit history (see `git log`). Original maintainers and contributors are credited in commit metadata. See `NOTICE` for the formal attribution.

## About Leborn

[Leborn](https://github.com/leborn-dev) is an initiative to revive popular but stalled open-source projects with AI-native enhancements designed for the 2026 era of software. Leborn is sponsored and operated by [LLL Sdn Bhd](https://lll.dev) (Malaysia).

The name "Leborn" is from "Reborn" with the R replaced by L (for LLL).

## License

This fork retains the original MIT license. See `LICENSE`.
