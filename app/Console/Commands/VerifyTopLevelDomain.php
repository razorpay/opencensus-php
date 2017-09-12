<?php

namespace RZP\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class VerifyTopLevelDomain extends GeneratorCommand
{
    // This is a text file published by IANA
    const TLD_URL = 'https://data.iana.org/TLD/tlds-alpha-by-domain.txt';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rzp:tld';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify Top Level Domain against an online listing';

    protected $type = 'Top-level Domain file';

    protected function buildClass($name)
    {
        $txt = file_get_contents(self::TLD_URL);

        $tlds = [];

        $tldRows = explode("\n", $txt);

        foreach ($tldRows as $line)
        {
            if (($this->isComment($line) === true) or
                (empty($line) === true))
            {
                continue;
            }

            $tlds[] = mb_strtolower(trim($line));
        }

        $tlds = array_unique($tlds);

        // Whitespaces for adding proper alignment to the
        // TLD class
        $tldStr = implode("',\n        '", $tlds);

        $replace = [
            'tldList' => '\''.$tldStr.'\''
        ];

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }

    protected function qualifyClass($name)
    {
        return 'RZP\\Constants\\TLD';
    }

    protected function alreadyExists($rawName)
    {
        return false;
    }

    protected function isComment($line)
    {
        return strpos(ltrim($line), '#') === 0;
    }

    protected function getStub()
    {
        return __DIR__.'/stubs/tlds.stub';
    }

    protected function getNameInput()
    {
        return '';
    }
}
