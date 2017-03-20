# PackagistAdder

[![Latest Version](https://img.shields.io/github/release/aurimasniekis/packagist-adder.svg?style=flat-square)](https://github.com/tasksuki/serializer/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/aurimasniekis/packagist-adder.svg?style=flat-square)](https://packagist.org/packages/tasksuki/serializer)

[![Email](https://img.shields.io/badge/email-aurimas@niekis.lt-blue.svg?style=flat-square)](mailto:aurimas@niekis.lt)

Submit's package from current git project to [Packagist.org](https://packagist.com)


## Install

Via Composer

```bash
$ composer global require aurimasniekis/packagist-adder
```

## Usage

At the projects root folder (where `composer.json` and `.git` folder exists) run:

Option `-p PAUTH cookie` or env variable `PACKAGIST_AUTH` required

```bash
$ packagist-adder [-p `pauth cookie`]
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.


## License

Please see [License File](LICENSE) for more information.
