<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class DocsGen extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'docs';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Generate documentation';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
        // We generate the documentation
        // First, check if apigen is available
        if($this->isApiGenInstalled() === false)
        {
            $this->error("You need to install apigen first. Make sure it is in $PATH");
        }
        else
        {
            $destination = $this->option('directory');
            $this->clean($destination);

            $this->info("Running command: apigen generate --destination '$destination'");

            shell_exec("apigen generate --destination '$destination'");

            $this->info("Documentation generated");
        }
	}

    private function clean($directory)
    {
        shell_exec("rm -rf '$directory'");
    }

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('directory', "d", InputOption::VALUE_OPTIONAL, 'Directory to store generated docs in', "./docs"),
		);
	}

    private function isApiGenInstalled()
    {
        $returnVal = shell_exec("which apigen");
        return (empty($returnVal) ? false : true);
    }

}
