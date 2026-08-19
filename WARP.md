# AGENTS.md - Working AI Reference for mailkit-api

## Project Overview
**Type**: PHP library
**Purpose**: Client library for the Mailkit email marketing platform API
**Status**: Active
**Repository**: git@github.com:Spoje-NET/mailkit-api.git
**License**: BSD-3-Clause

## Key Technologies
- PHP >= 8.1 with native backed enums
- Composer
- nette/utils ^4.0 (Strings, Json, DateTime)
- ext-xmlrpc (XML-RPC API communication)
- Nette Tester (testing framework)
- PHPStan (static analysis)
- PHP CS Fixer with ergebnis/php-cs-fixer-config

## Architecture & Structure
```
mailkit-api/
├── src/
│   ├── DataObjects/          # Data models (User, MailingList, Message, etc.)
│   │   └── Enums/            # Native PHP 8.1 backed enums
│   ├── Exceptions/           # Exception hierarchy
│   ├── Managers/             # API endpoint managers (Users, MailingLists, Messages, WebHooks)
│   ├── Responses/            # Response interfaces
│   ├── Results/              # API method result objects
│   ├── RPC/                  # RPC layer
│   │   ├── Adapters/         # XML-RPC and JSON adapters
│   │   ├── Exceptions/       # RPC-specific exceptions
│   │   └── Responses/        # RPC response objects
│   └── MailkitApi.php        # Main entry point
├── tests/
│   └── IgloonetTests/        # Nette Tester test suite (.phpt files)
├── .php-cs-fixer.dist.php    # CS Fixer configuration
├── phpstan-default.neon.dist # PHPStan configuration
└── Makefile                  # Build targets
```

## API Endpoints
The library communicates with:
- XML-RPC: https://api.mailkit.eu/rpc.fcgi
- JSON: https://api.mailkit.eu/json.fcgi

API docs: https://www.mailkit.com/resources/api/api-introduction

## Development Workflow

### Setup
```bash
git clone git@github.com:Spoje-NET/mailkit-api.git
cd mailkit-api
composer install
```

### Testing
```bash
make tests                     # Run Nette Tester tests
make static-code-analysis      # Run PHPStan
make cs                        # Fix coding standards
```

## Important Patterns
- Enums use native PHP 8.1 backed enums (not Consistence library)
- Enum values are created via `::from()` / `::tryFrom()`, not `::get()`
- `Strings::startsWith`/`endsWith` replaced with native `str_starts_with`/`str_ends_with`
- Tests use Nette Tester (.phpt files), NOT PHPUnit
- Mock adapters in tests/ load XML/JSON fixture files to simulate API responses
