# Contributing

Thank you for your interest in contributing to this project. We value your contributions and have established these guidelines to ensure a smooth collaboration process.

## Getting Started

### Prerequisites

- PHP 8.4 or higher
- Composer

### Setup

1. Fork the repository to your GitHub account
2. Clone your fork locally:
   ```bash
   git clone https://github.com/your-username/:package_name.git
   cd :package_name
   ```
3. Install dependencies:
   ```bash
   composer install
   ```

## Development Workflow

### Making Changes

1. Create a feature branch from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   ```
2. Make your changes, following the project's coding standards
3. Write or update tests as needed
4. Ensure all tests pass and code meets quality standards
5. Commit your changes with clear, descriptive commit messages
6. Push your branch to your fork
7. Open a pull request against the `main` branch

### Pull Request Guidelines

- **Description**: Provide a clear description of your changes and their purpose. Reference any related issues.
- **Template**: Follow the [pull request template](.github/PULL_REQUEST_TEMPLATE.md) if one exists.
- **Scope**: Keep pull requests focused. Submit separate PRs for unrelated changes.
- **Commit History**: Maintain a clean commit history. Each commit should represent a logical unit of change.
- **Rebase**: You may need to [rebase](https://git-scm.com/book/en/v2/Git-Branching-Rebasing) your branch to resolve conflicts with the main branch.

## Code Quality Standards

### Linting

Ensure your code adheres to the project's coding style:

```bash
composer lint
```

### Testing

All contributions must include appropriate test coverage.

Run the full test suite:
```bash
composer test
```

Run type checks:
```bash
composer test:types
```

Run unit tests:
```bash
composer test:unit
```

### Code Review

All submissions require review before merging. Reviewers will assess:

- Code quality and adherence to project standards
- Test coverage and quality
- Documentation completeness
- Impact on existing functionality

## Versioning

This project follows [Semantic Versioning](https://semver.org/). When contributing:

- **Bug fixes**: Patch version increment
- **New features**: Minor version increment (backward-compatible)
- **Breaking changes**: Major version increment

Please indicate in your pull request if your change introduces a breaking change.

## Questions or Issues

If you have questions about contributing, please open an issue for discussion before starting significant work. This helps ensure your contribution aligns with the project's direction and avoids duplicate effort.

## License

By contributing to this project, you agree that your contributions will be licensed under the same license as the project.
