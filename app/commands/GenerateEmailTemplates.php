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
		$templates = [
            'emails/payment/customer', 'emails/merchant/activation', 'emails/partials/header', 'emails/partials/footer', 'emails/partials/header_image',
            'emails/partials/separator', 'emails/merchant/daily_report'
        ];

        $view_directory = app_path()."/views/";
        $ink_css =      file_get_contents($view_directory.'css/ink.css');
        $common_css =   file_get_contents($view_directory.'css/email.css');

        $base_css = $ink_css. PHP_EOL . $common_css;

        foreach ($templates as $template) {
            $cssContent = $base_css;
            $css_file = $view_directory.$template.".css";

            if(file_exists($css_file))
            {
                $cssContent .= PHP_EOL . file_get_contents($css_file);
            }

            $emailTemplate = file_get_contents($view_directory.$template.".email");
            $convertor = new CssToInlineStyles();
            $convertor->setHTML($emailTemplate);
            $convertor->setCleanup(false);
            $convertor->setExcludeMediaQueries(false);
            $convertor->setCSS($cssContent);

            // We run decode because some entities '{' get converted by cssInliner
            // TODO: Find a better solution to this
            // This should only be applied on img src tags
            $output = str_replace(["%7B", "%7D", "%24", "%5B", "%5D", '%20', '&gt;', '&lt;'], ['{','}', '$', '[', ']', ' ', '>', '<'], ($convertor->convert()));
            $renderFile = "$view_directory$template.blade.php";
            file_put_contents($renderFile, $output);
            $this->info("Rendered $template into $renderFile");
        }
	}

}
