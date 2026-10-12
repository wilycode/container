# Contributing

Thanks for your interest in WilyCode Container.

1. Open an issue to discuss bugs or proposed API changes.
2. Fork the repository and create a focused branch.
3. Add or update tests for changes to behavior.
4. Run the checks before opening a pull request:

   ```bash
   composer install
   composer validate --strict
   composer test
   ```

Keep changes small, follow the existing PSR-11 API, and avoid adding runtime dependencies without a clear need. Breaking changes must be documented.

For the library directory and related components, see the **[WilyCode PHP Ecosystem](https://github.com/wilycode/wilycode)**.
