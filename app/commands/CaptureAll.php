<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CaptureAll extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'custom:captureall';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Captures all the pending transactions, awaiting capture';

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
		//Call transaction controller's capture fucntion
		$transactionController=new TransactionController;
		$response = $transactionController->capture();

		$captures=$response->getContent();

		$captures = json_decode($captures);

		//Check if output is not NULL
		if (! $captures) return;

		//Display the captured transaction's ids
		foreach($captures as $capture)
		{
			if ($capture->captured)
			{
				$this->info($capture->id." Captured successfully. \n");
			}
		}
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	/*protected function getArguments()
	{
		return array(
			array('example', InputArgument::REQUIRED, 'An example argument.'),
		);
	}*/

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	/*protected function getOptions()
	{
		return array(
			array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
		);
	}*/

}
