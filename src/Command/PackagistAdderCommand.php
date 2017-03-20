<?php

namespace PackagistAdder\Command;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PackagistAdderCommand
 *
 * @package PackagistAdder\Command
 * @author  Aurimas Niekis <aurimas@niekis.lt>
 */
class PackagistAdderCommand extends Command
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var string
     */
    private $pauth;

    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $session;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('packagist:adder')
            ->setDescription('Submit\'s package from current git project to Packagist.org')
            ->addOption(
                'pauth',
                'p',
                InputOption::VALUE_REQUIRED,
                'Packagist auth cookie or PACKAGIST_AUTH env variable'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $output->writeln('<info>PackagistAdder v' . $this->getApplication()->getVersion() . ' by Aurimas Niekis <aurimas@niekis.lt></info>');
        $output->writeln('');

        if (null !== $input->getOption('pauth')) {
            $this->pauth = $input->getOption('pauth');
        } elseif (isset($_SERVER['PACKAGIST_AUTH'])) {
            $this->pauth = $_SERVER['PACKAGIST_AUTH'];
        } else {
            $output->writeln('<error>No valid PAUTH provided!</error>');

            return 1;
        }

        if (false === $this->checkRequirements()) {
            return 1;
        }

        $url = $this->parseConfigFile();
        if (false === $url) {
            $output->writeln('<error>No valid git remote origins found!</error>');

            return 1;
        }

        if (false === $this->fetchCSRFToken()) {
            $output->writeln('<error>No valid CSRF token found!</error>');

            return 1;
        }

        if (false === $this->validateUrl($url)) {
            $output->writeln('<error>No valid package found in "' . $url . '"</error>');

            return 1;
        }

        if (false === $this->submitPackage($url)) {
            $output->writeln('<error>Error creating package!</error>');

            return 1;
        }


        $output->writeln('');
        $output->writeln('<info>Done.</info>');

        return 0;
    }

    private function checkRequirements()
    {
        if (false === file_exists($this->getGitConfigFile())) {
            $this->output->writeln(
                sprintf(
                    '<error>`.git/config` file not found in "%s"</error>',
                    getcwd()
                )
            );

            return false;
        }

        if (false === file_exists($this->getComposerJsonFile())) {
            $this->output->writeln(
                sprintf(
                    '<error>`composer.json` file not found in "%s"</error>',
                    getcwd()
                )
            );

            return false;
        }

        return true;
    }

    private function getGitConfigFile()
    {
        return getcwd() . '/.git/config';
    }

    private function getComposerJsonFile()
    {
        return getcwd() . '/composer.json';
    }

    private function parseConfigFile()
    {
        $file = $this->getGitConfigFile();

        $ini = parse_ini_file($file, true);

        foreach ($ini as $group => $config) {
            if (0 === strpos($group, 'remote')) {
                return $config['url'];
            }
        }

        return false;
    }

    private function validateUrl($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://packagist.org/packages/fetch-info');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $a = 'package[repository]=' . urlencode($url) . '&package[_token]=' . urlencode($this->token);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            $a
        );
        curl_setopt($ch, CURLOPT_POST, 1);

        $headers   = [];
        $headers[] = 'Cookie: pauth=' . $this->pauth . '; packagist=' . $this->session . ';';
        $headers[] = 'Origin: https://packagist.org';
        $headers[] = 'X-Requested-With: XMLHttpRequest';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->output->writeln(
                sprintf(
                    '<error>Curl error when trying to validate url "%s"</error>',
                    curl_error($ch)
                )
            );

            return false;
        }

        curl_close($ch);

        $result = json_decode($result, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            $this->output->writeln(
                sprintf(
                    '<error>Error parsing JSON response: %s</error>',
                    json_last_error_msg()
                )
            );

            return false;
        }

        return $result['status'] === 'success';
    }

    private function submitPackage($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://packagist.org/packages/submit');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $a = 'package[repository]=' . urlencode($url) . '&package[_token]=' . urlencode($this->token);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            $a
        );
        curl_setopt($ch, CURLOPT_POST, 1);

        $headers   = [];
        $headers[] = 'Cookie: pauth=' . $this->pauth . '; packagist=' . $this->session . ';';
        $headers[] = 'Origin: https://packagist.org';
        $headers[] = 'X-Requested-With: XMLHttpRequest';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 1);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->output->writeln(
                sprintf(
                    '<error>Curl error when trying to validate url "%s"</error>',
                    curl_error($ch)
                )
            );

            return false;
        }

        curl_close($ch);

        $line = strtok($result, "\n");

        return false !== strpos($line, '302');
    }

    private function fetchCSRFToken()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://packagist.org/packages/submit");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        $headers = array();
        $headers[] = 'Cookie: pauth=' . $this->pauth . ';';
        $headers[] = 'Origin: https://packagist.org';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 1);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $this->output->writeln(
                sprintf(
                    '<error>Curl error when trying to fetch CSRF token "%s"</error>',
                    curl_error($ch)
                )
            );

            return false;
        }

        curl_close($ch);

        if (preg_match('/packagist=([^;]+)/', $result, $matches)) {
            $this->session = $matches[1];
        } else {
            return false;
        }

        if (preg_match('/name="package\[_token\]" class=" form-control" value="([^"]+)"/', $result, $matches)) {
            $this->token = $matches[1];

            return true;
        } else {
            return false;
        }
    }
}