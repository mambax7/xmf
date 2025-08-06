![alt XOOPS CMS](https://xoops.org/images/logoXoopsPhp81.png)
# Contributing to [XOOPS CMS](https://xoops.org)
[![XOOPS CMS Module](https://img.shields.io/badge/XOOPS%20CMS-Module-blue.svg)](https://xoops.org)
[![Software License](https://img.shields.io/badge/license-GPL-brightgreen.svg?style=flat)](https://www.gnu.org/licenses/gpl-2.0.html)

Contributions are **welcome** and will be fully **credited**.

We accept contributions via Pull Requests on [GitHub](https://github.com/XoopsModules25x/mymenus).

## Pull Requests

- **[PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)** - The easiest way to apply the conventions is to install [PHP Code Sniffer](http://pear.php.net/package/PHP_CodeSniffer).
- **Add tests!** - We encourage providing tests for your contributions.
- **Document any change in behavior** - Make sure the `/docs/changelog.txt` and any other relevant documentation are up-to-date.
- **Consider our release cycle** - We try to follow [Semantic Versioning v2.0.0](http://semver.org/). Randomly breaking public APIs is not an option.
- **Create feature branches** - Don't ask us to pull from your master branch.
- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.
- **Send coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please squash them before submitting.

## Developer Tools

This project uses `composer` to manage dependencies and run development tools. Make sure you have Composer installed (see [getcomposer.org](https://getcomposer.org/)).

After cloning the repository, install the development dependencies:

```bash
composer install
```

### Coding Standards

You can check for coding standard violations by running:

```bash
composer check-style
```

To automatically fix many of the issues, run:

```bash
composer fix-style
```

### Static Analysis

This project uses [PHPStan](https://phpstan.org/) for static analysis. To run it, use:

```bash
composer analyze
```

It is recommended to run these tools before submitting a pull request.

Happy coding, and **_May the Source be with You_**!
