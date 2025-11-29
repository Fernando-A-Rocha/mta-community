# Contributing to the MTA Community Platform

Thank you for your interest in helping us rebuild and expand the MTA community platform (`community.multitheftauto.com`). The goal is to preserve all existing capabilities while delivering new features, improved performance, and a modernized UI/UX. Every contribution—code, design, documentation, or feedback—helps us deliver a better experience for the MTA community.

## Ways to Contribute
- **Bug reports & issues:** Tell us what is broken, confusing, or missing. Attach screenshots, reproduction steps, and logs whenever possible.
- **Feature ideas & UX improvements:** Describe the use case, outline expected behavior, and explain why it benefits the community.
- **Code contributions:** Fix bugs, implement new functionality, improve tests, or polish documentation.
- **Design & accessibility feedback:** Highlight opportunities to make the interface clearer, faster, and more inclusive.

## Getting Started
1. **Fork and clone** this repository.
2. **Install dependencies** (PHP, Composer, Node.js, npm, Docker if needed). Follow `README.md` for environment setup.
3. **Create a feature branch** from `main` using a descriptive name, e.g. `feature/profile-search` or `fix/avatar-upload`.
4. **Sync regularly** with `main` to avoid large merge conflicts.

## Development Workflow
1. **Open an issue (or comment on an existing one)** to discuss significant changes before building.
2. **Implement the change** with clean, self-documenting code. Favor Laravel best practices, typed PHP (`declare(strict_types=1);`), PSR-12 style, and SOLID design.
3. **Add/Update tests** (PHPUnit, Pest, Dusk) that cover the new behavior. Include seeders/factories if data is required.
4. **Run the full test suite & linters** before pushing.
5. **Document** new commands, migrations, environment variables, or UI changes in `README.md`, `docs/`, or inline comments as appropriate.

## Pull Request Guidelines
- **Reference the related issue** in the PR description (`Fixes #123`).
- **Describe the change set** (what, why, screenshots if UI-related, and testing performed).
- **Keep PRs focused**; large unrelated changes slow reviews.
- **Ensure at least one reviewer** from the maintainers signs off before merging. No self-merges without review.
- **Resolve review feedback promptly**. Clarify decisions with reviewers when necessary.

## Coding Standards
- **Framework:** Laravel (latest LTS) with Composer-managed dependencies.
- **Architecture:** Follow MVC, service containers, Form Requests for validation, Eloquent for persistence, and Laravel’s queue/events ecosystem for async work.
- **Security:** Use CSRF protection, validation rules, authorization policies/gates, encrypted secrets, and rate limiting where needed.
- **Performance:** Profile queries, use caching/pagination, and avoid N+1 issues.
- **Testing:** Favor feature and integration tests for user-facing flows; write unit tests for isolated logic.

## Issue Reporting Checklist
- Search existing issues first to avoid duplicates.
- Provide a clear title and concise summary.
- Detail steps to reproduce, expected vs. actual behavior, and environment info (browser, OS, PHP version, etc.).
- Attach logs, stack traces, screenshots, or videos when relevant.

## Community Expectations
- Be respectful and collaborative; welcome newcomers.
- Assume positive intent and focus on solutions.
- Follow open-source etiquette: respond within a reasonable time, keep discussions on-topic, and credit contributors.

By following these guidelines, you help us deliver a modern, feature-rich community hub for every MTA player and creator. We appreciate your time and contributions!