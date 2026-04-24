[![CI](https://github.com/voku/PHPDoctor/actions/workflows/ci.yml/badge.svg)](https://github.com/voku/PHPDoctor/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/voku/PHPDoctor/v/stable)](https://packagist.org/packages/voku/PHPDoctor) 
[![License](https://poser.pugx.org/voku/PHPDoctor/license)](https://packagist.org/packages/voku/PHPDoctor)
[![Donate to this project using Paypal](https://img.shields.io/badge/paypal-donate-yellow.svg)](https://www.paypal.me/moelleken)
[![Donate to this project using Patreon](https://img.shields.io/badge/patreon-donate-yellow.svg)](https://www.patreon.com/voku)

# 🏥 ***PHPDoc***tor

Check PHP files or directories for missing types.

If you already use [PHPStan](https://phpstan.org/r/db8ec6af-8815-444e-b533-2717ccb643c6) for your type checks but sometimes someone 
in the team still commit non typed code, then PHPDoctor is for you.

### Install via "phar" (**recommended**)

https://github.com/voku/PHPDoctor/releases

### Install via "composer require"

```shell
composer require-dev voku/phpdoctor
```

### Quick Start

```
Usage:
  analyse [options] [--] <path...>

Arguments:
  path                                                                                   The path to analyse

Options:
      --autoload-file[=AUTOLOAD-FILE]                                                    The path to your autoloader. [default: ""]
      --access[=ACCESS]                                                                  Check for "public|protected|private" methods. [default: "public|protected|private"]
      --skip-ambiguous-types-as-error[=SKIP-AMBIGUOUS-TYPES-AS-ERROR]                    Skip check for ambiguous types. (false or true) [default: "false"]
      --skip-deprecated-functions[=SKIP-DEPRECATED-FUNCTIONS]                            Skip check for deprecated functions / methods. (false or true) [default: "false"]
      --skip-functions-with-leading-underscore[=SKIP-FUNCTIONS-WITH-LEADING-UNDERSCORE]  Skip check for functions / methods with leading underscore. (false or true) [default: "false"]
      --skip-parse-errors[=SKIP-PARSE-ERRORS]                                            Skip parse errors in the output. (false or true) [default: "true"]
      --path-exclude-regex[=PATH-EXCLUDE-REGEX]                                          Skip some paths via regex e.g. "#/vendor/|/other/.*/path/#i" [default: "#/vendor/|/tests/#i"]
```

### Demo

Parse a string:
```php
$code = '
<?php declare(strict_types = 1);   
     
class HelloWorld
{
    /**
     * @param mixed $date
     */ 
    public function sayHello($date): void
    {
        echo \'Hello, \' . $date->format(\'j. n. Y\');
    }
}';

$phpdocErrors = PhpCodeChecker::checkFromString($code);

// [8]: missing parameter type for HelloWorld->sayHello() | parameter:date']
```

### Ignore errors

You can use ```<phpdoctor-ignore-this-line/>``` in @param or @return phpdocs to ignore the errors directly in your code.

```php
/**
 * @param mixed $lall <p>this is mixed but it is ok, because ...</p> <phpdoctor-ignore-this-line/>
 *
 * @return array <phpdoctor-ignore-this-line/>
 */
function foo_ignore($lall) {
    return $lall;
}
```

### Building the PHAR file

The PHAR is built automatically via GitHub Actions whenever a new version tag (`v*.*.*`) is pushed.
The resulting `phpdoctor.phar` is attached to the corresponding [GitHub Release](https://github.com/voku/PHPDoctor/releases).

To build locally, install [humbug/box](https://github.com/box-project/box) and run:

```bash
# install production dependencies only
composer install --no-dev --optimize-autoloader

# download box (or install via phive: phive install humbug/box)
curl -L https://github.com/box-project/box/releases/latest/download/box.phar -o box.phar

# compile the PHAR
php box.phar compile --config=box.json.dist
```

### Support

For support and donations please visit [Github](https://github.com/voku/PHPDoctor/) | [Issues](https://github.com/voku/PHPDoctor/issues) | [PayPal](https://paypal.me/moelleken) | [Patreon](https://www.patreon.com/voku).

For status updates and release announcements please visit [Releases](https://github.com/voku/PHPDoctor/releases) | [Twitter](https://twitter.com/suckup_de) | [Patreon](https://www.patreon.com/voku/posts).

For professional support please contact [me](https://about.me/voku).

### Thanks

- Thanks to [GitHub](https://github.com) (Microsoft) for hosting the code and a good infrastructure including Issues-Management, etc.
- Thanks to [IntelliJ](https://www.jetbrains.com) as they make the best IDEs for PHP and they gave me an open source license for PhpStorm!
- Thanks to [GitHub Actions](https://github.com/features/actions) for the awesome CI/CD platform!
- Thanks to [StyleCI](https://styleci.io/) for the simple but powerful code style check.
- Thanks to [PHPStan](https://github.com/phpstan/phpstan) && [Psalm](https://github.com/vimeo/psalm) for really great Static analysis tools and for discover bugs in the code!
