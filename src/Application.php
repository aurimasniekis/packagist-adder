<?php

namespace PackagistAdder;

use PackagistAdder\Command\PackagistAdderCommand;
use Symfony\Component\Console\Application as BaseApplication;

/**
 * Class Application
 *
 * @package PackagistAdder
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('PackagistAdder', '1.0.0');

        $this->add(new PackagistAdderCommand());

        $this->setDefaultCommand('packagist:adder', true);
    }
}