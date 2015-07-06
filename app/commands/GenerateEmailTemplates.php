<?php

use Illuminate\Console\Command;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class GenerateEmailTemplates extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'email:gen';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Converts email templates to use inline styles';

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
        // This is a map of templates and the rendered file names
		$templates = ['emails/payment/customer'];

        $view_directory = app_path()."/views/";
        $ink_css = file_get_contents($view_directory.'css/ink.css');

        foreach ($templates as $template) {
            $cssFile = $view_directory.$template.".css";
            $cssContent = $ink_css . PHP_EOL . file_get_contents($cssFile);
            $emailTemplate = file_get_contents($view_directory.$template.".email");

            $convertor = new CssToInlineStyles();
            $convertor->setHTML($emailTemplate);
            $convertor->setCleanup(false);
            $convertor->setExcludeMediaQueries(false);
            $convertor->setCSS($cssContent);

            // We run decode because some entities '{' get converted by cssInliner
            // TODO: Find a better solution to this
            $output = str_replace(["%7B", "%7D", "%24", "%5B", "%5D"], ['{','}', '$', '[', ']'], ($convertor->convert()));
            $renderFile = "$view_directory$template.blade.php";
            file_put_contents($renderFile, $output);
        }

        $this->info("Rendered $template into $renderFile");
	}

}
