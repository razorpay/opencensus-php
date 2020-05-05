<?php

namespace RZP\Console\Commands;

use Illuminate\Console\Command;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class GenerateEmailTemplates extends Command
{

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
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // This is a map of templates and the rendered file names
        // Keep this list sorted
        $templates = [
            'emails/merchant/activation_heimdall',
            'emails/merchant/authorized_reminder',
            'emails/merchant/bankaccount_change',
            'emails/merchant/bankaccount_change_request',
            'emails/merchant/daily_report',
            'emails/merchant/daily_report_debug',
            'emails/merchant/newsletter',
            'emails/merchant/holiday_notification',
            'emails/merchant/settlement_failure',
            'emails/merchant/payzapp',
            'emails/merchant/welcome',
            'emails/merchant/fee_credits_alert',
            'emails/merchant/negative_balance_alert',
            'emails/merchant/negative_balance_threshold_alert',
            'emails/merchant/balance_now_positive_alert',
            'emails/merchant/reserve_balance_activate_alert',
            'emails/merchant/negative_balance_breach_reminder',

            'emails/partials/footer',
            'emails/partials/header',
            'emails/partials/header_image',
            'emails/partials/separator',

            'emails/payment/customer',
            'emails/payment/failed_to_authorized',
            'emails/payment/merchant',

            'emails/refund/common',

            'emails/admin/user',

            'emails/invoice/notification',
            'emails/invoice/customer/notification',
            'emails/invoice/merchant/captured',

            'emails/oauth/app_authorization',

            'emails/subscription/charged',
            'emails/subscription/cancelled',
            'emails/subscription/pending',
            'emails/subscription/halted',
            'emails/subscription/completed',
            'emails/subscription/card_changed',
            'emails/subscription/authenticated',

            'emails/dispute/creation',
            'emails/dispute/accepted_admin',
            'emails/dispute/files_submitted_admin',

            'emails/merchant/add_sub_merchant_mail_partner',
            'emails/merchant/add_sub_merchant_affiliate',
            'emails/user/mapped_to_account',
        ];

        $view_directory = app_path().'/../resources/views/';
        $ink_css =      file_get_contents($view_directory.'css/ink.css');
        $common_css =   file_get_contents($view_directory.'css/email.css');

        $base_css = $ink_css. PHP_EOL . $common_css;

        foreach ($templates as $template)
        {
            $cssContent = $base_css;
            $css_file = $view_directory.$template.".css";

            if (file_exists($css_file))
            {
                $cssContent .= PHP_EOL . file_get_contents($css_file);
            }

            $emailTemplate = file_get_contents($view_directory.$template.".email");

            $converter = new CssToInlineStyles;

            $msg = $converter->convert($emailTemplate, $cssContent);

            // We run decode because some entities '{' get converted by cssInliner
            // TODO: Find a better solution to this
            // This should only be applied on img src tags
            $output = str_replace(
                ["%7B", "%7D", "%24", "%5B", "%5D", '%20', '&gt;', '&lt;'], ['{','}', '$', '[', ']', ' ', '>', '<'],
                $msg);

            $renderFile = "$view_directory$template.blade.php";

            file_put_contents($renderFile, $output);

            $this->info("Rendered $template into $renderFile");
        }
    }
}
