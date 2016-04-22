<?php
namespace Tests\Integration;

use Selenium\Locator as l;
use Laracasts\TestDummy\Factory;
use Models;
use URL;
use Uuid;
use Exception;

class MerchantTest extends TestCase
{
    protected $merchant;

    protected static $migrated = false;

    public function setUp()
    {
        parent::setUp();

        Factory::$factoriesPath = __DIR__.'/../factories/';

        if (static::$migrated === false)
        {
            // Truncates all tables befor first test
            $this->truncateAll();
            static::$migrated = true;
        }

        // Creates a new merchant if none exists in db otherwise uses that. This is necessary for persisting sessions between tests
        try
        {
            $this->merchant = Models\Merchant\Entity::firstorfail();
            $this->user = Models\User\Entity::firstorfail();
        }
        catch(Exception $e)
        {
            $businessName = random_alpha_string(10);

            $this->merchant = $this->buildEntity('merchant', array(
                'id'    => Uuid::generate(),
                'email' => static::generateMerchantEmail(),
                'name'  => $businessName
            ));
        }

        // Make sure that the merchant has webhook tagged
        // So the webhook button is visible
        $this->merchant->tag('webhooks', 'team', 'orders');
    }

    /**
     * Tests merchant registration
     */
    public function testRegister()
    {
        $businessName = random_alpha_string(6). ' Merchant';

        $this->browser
            // Visits the 'register page
            ->open('/admin')
            ->open('/#/access/signup')
            ->waitForPresent('form[name="signup"]')
            ->type(l::IdOrName('business_name'), $businessName)
            // Fill name
            ->type(l::IdOrName('name'), $this->merchant->name)
            // Fill email
            ->type(l::IdOrName('email'), $this->merchant->email)
            ->type(l::IdOrName('contact_mobile'), '9999999999')
            ->type(l::IdOrName('password'), '123456xx')
            ->type(l::IdOrName('password_confirmation'), '123456xx')
            ->click(l::IdOrName('agree'))
            // Click in the button
            ->click(l::IdOrName('submit'))
            ->waitForPresent('.alert-success');

        $this->assertBodyHasText("Please check your inbox for confirmation email from Razorpay");
    }

    /**
     * Tests merchant confirmation
     */
    public function testUserConfirmation()
    {
        $confirm_token = $this->user->confirm_token;
       $this->browser
            ->open('/admin')
            ->open('/#/access/confirm/'.$confirm_token)    // Visits the 'register page
            //->waitForCondition("selenium.browserbot.getCurrentWindow().$('.alert-success').length > 0", 20000);
            ->waitForPresent('.alert-success');

        $this->assertBodyHasText("Confirmation successful");
    }

    /**
     * Tests merchant login
     */
    public function testLogin()
    {
        $this->browser
            ->waitForPresent('form[name="signin"]')
            // Fill name
            ->type(l::IdOrName('email'), $this->merchant->email)
            // Fill password
            ->type(l::IdOrName('password'), '123456xx')
            // Click in the button
            ->click(l::IdOrName('submit'))
            // Wait for page to load
            ->waitForPresent('.navbar');

        $this->assertBodyHasText("Welcome to Razorpay");

        $this->assertBodyHasText("Total Payments");

        $this->assertBodyHasText("Successful Transactions");
    }

    public function testWebhooks()
    {
        // Testing webhooks display
         $this->browser
            ->waitAndClickById('webhookNav')
            ->waitForPresent('.webhooks-table')
            ->waitForLoaded();

        $this->assertBodyHasText("Webhooks");

        // Testing create wevhook
        $this->browser
            ->click(l::IdOrName('createWebook'))
            ->waitForPresent('.new-webhook-modal');

        $this->assertBodyHasText("New Webhook");

        // Check if display includes new webhook
        $this->browser
            ->type(l::IdOrName('new_webhook_url'), 'http://googleeee.com')
            ->click(l::css('.modal-ok'))
            ->waitForAbsent('.new-webhook-modal')
            ->waitForLoaded();

        $this->assertFalse($this->browser->isError());

        $this->assertBodyHasText("Webhook added");
        $this->assertBodyHasText('http://googleeee.com');

        // Testing editing webhook
        $this->browser
            ->waitAndClickById('editWebook')
            ->waitForPresent('.edit-webhook-modal')
            ->type(l::IdOrName('edit_webhook_url'), 'https://googleeee.com')
            ->click(l::css('.modal-ok'))
            ->waitForLoaded();

        $this->assertFalse($this->browser->isError());

        $this->assertBodyHasText("Webhook edited");
        $this->assertBodyHasText('https://googleeee.com');
    }

    /**
     * Tests payments panel display
     */
    public function testPaymentsList()
    {
        $this->browser
            ->waitAndClickById('paymentsNav')
            ->waitForPresent('.payments-table')
            ->waitForLoaded();

        $this->assertBodyHasText("List of all payments");
    }

    /**
     * Tests refunds panel display
     */
    public function testRefundsPanel()
    {
        $this->browser
            ->waitAndClickById('refundsNav')
            ->waitForPresent('.refunds-table')
            ->waitForLoaded();

        $this->assertBodyHasText("List of all refunds");
    }

    /**
     * Tests settlements panel display
     */
    public function testSettlementsPanel()
    {
        $this->markTestSkipped();
    }

    /**
     * Tests keys panel display and rolling of keys
     */
    public function testKeysPanel()
    {
        // Testing keys display
         $this->browser
            ->waitAndClickById('keysNav')
            ->waitForPresent('.keys-table')
            ->waitForLoaded();

        $this->assertBodyHasText("API Keys");

        $this->assertBodyHasText("Key Id");

        // Testing Generate new Key
        $this->browser
            ->click(l::IdOrName('generateKey'))
            ->waitForPresent('.new-key-modal');

        $this->assertBodyHasText("New Key");

        $this->assertBodyHasText("Key Generated");

        // New Key generated close the modal
        $this->browser
            ->click(l::css('.modal-ok'))
            ->waitForPresent('.confirm-modal')
            ->click(l::css('.confirm-ok'))
            ->waitForAbsent('.new-key-modal')
            ->waitForLoaded();

        $this->assertFalse($this->browser->isError());

        $this->assertBodyHasText("Key Generated");

        // Testing rolling of key
        $this->browser
            ->click(l::css('.roll_key'))
            ->waitForPresent('.roll-key-modal')
            ->click(l::css('.modal-ok'))
            ->waitForPresent('.new-key-modal')
            ->click(l::css('.modal-ok'))
            ->waitForPresent('.confirm-ok')
            ->click(l::css('.confirm-ok'))
            ->waitForAbsent('.new-key-modal')
            ->waitForLoaded();

        $this->assertFalse($this->browser->isError());

        $this->assertBodyHasText("Key Rolled");
    }

    /**
     * Tests activation panel display and form filling
     */
    public function testActivationPanel()
    {
        // Testing keys display
         $this->browser
            ->waitAndClickById('activationNav')
            ->waitForPresent('.activation-wrapper')
            ->waitForLoaded();

        $this->assertBodyHasText("Contact Details");

        // Fill in Contact details and save
        $this->browser
            ->click(l::css('form[name="step1"] > fieldset > .prev-next > .btn-save'))
            ->waitForPresent('form[name="step1"] > fieldset > .alerts > .alert-danger')
            ->type(l::IdOrName('contact_name'), 'Yo Name')   // Fill name
            ->type(l::IdOrName('contact_email'), 'email@umail.com')   // Fill slug
            ->type(l::IdOrName('transaction_report_email'), 'billing@umail.com')
            ->type(l::IdOrName('contact_mobile'), '9199192999')
            ->type(l::IdOrName('contact_landline'), '9191929393')
            ->click(l::css('form[name="step1"] > fieldset > .prev-next > .btn-save'))
            ->waitForPresent('form[name="step1"] > fieldset > .alerts > .alert-success');

        $this->assertFalse($this->browser->isElementPresent(l::css('form[name=\"step1\"] > fieldset > .alerts > .alert-danger')));

        // Fill in business Details and save
        $this->browser
            ->click(l::css('form[name="step1"] > fieldset > .prev-next > .btn-next'))
            ->waitForVisible('form[name="step2"]')
            ->select(l::IdOrName('business_type'), 'Partnership')
            ->type(l::IdOrName('business_name'), 'Test Company Pvt Ltd')
            ->type(l::IdOrName('business_dba'), 'Tester')
            ->select(l::IdOrName('business_international'), 'Yes')
            ->type(l::IdOrName('business_paymentdetails'), 'Details of Payment')
            ->type(l::IdOrName('business_registered_address'), 'address 1, 2')
            ->type(l::IdOrName('business_registered_state'), 'state')
            ->type(l::IdOrName('business_registered_city'), 'city')
            ->type(l::IdOrName('business_registered_pin'), '333333')
            ->click(l::IdOrName('or_same'))
            ->type(l::IdOrName('business_doe'), '1990-11-01')
            ->type(l::IdOrName('company_cin'), 'cin123455')
            ->type(l::IdOrName('company_pan'), 'pan12345')
            ->type(l::IdOrName('company_pan_name'), 'pan name')
            ->type(l::IdOrName('business_model'), 'my model')
            ->select(l::IdOrName('transaction_volume'), '1 to 10 lakh')
            ->type(l::IdOrName('transaction_value'), '120')
            ->type(l::IdOrName('promoter_pan'), 'PAE123')   // Fill name
            ->type(l::IdOrName('promoter_pan_name'), 'Promoter')   // Fill slug
            ->click(l::css('form[name="step2"] > fieldset > .prev-next > .btn-save'))
            ->waitForPresent('form[name="step2"] > fieldset > .alerts > .alert-success');

        $this->assertFalse($this->browser->isElementPresent(l::css('form[name=\"step2\"] > fieldset > .alerts > .alert-danger')));

        // Fill in Promoters Details and save
        $this->browser
            ->click(l::css('form[name="step2"] > fieldset > .prev-next > .btn-next'))
            ->waitForVisible('form[name="step3"]')
            ->type(l::IdOrName('business_website'), 'http://testing.com')
            ->type(l::IdOrName('website_about'), 'http://testing.com')
            ->type(l::IdOrName('website_contact'), 'http://testing.com')
            ->type(l::IdOrName('website_privacy'), 'http://testing.com')
            ->type(l::IdOrName('website_terms'), 'http://testing.com')
            ->type(l::IdOrName('website_refund'), 'http://testing.com')
            ->type(l::IdOrName('website_pricing'), 'http://testing.com')
            ->click(l::css('form[name="step3"] > fieldset > .prev-next > .btn-save'))
            ->waitForPresent('form[name="step3"] > fieldset > .alerts > .alert-success');

        $this->assertFalse($this->browser->isElementPresent(l::css('form[name=\"step3\"] > fieldset > .alerts > .alert-danger')));

        // Fill in Bank Account Details and save
        $this->browser
            ->click(l::css('form[name="step3"] > fieldset > .prev-next > .btn-next'))
            ->waitForVisible('form[name="step4"]')
            ->type(l::IdOrName('bank_name'), 'BankName')   // Fill name
            ->type(l::IdOrName('bank_account_number'), 'RZP123443')   // Fill slug, alphanumeric
            ->type(l::IdOrName('bank_account_name'), 'Tester')   // Fill slug
            ->type(l::IdOrName('bank_account_type'), 'savings')   // Fill slug
            ->type(l::IdOrName('bank_branch_address'), 'Abcd')   // Fill slug
            ->type(l::IdOrName('bank_branch_ifsc'), 'abc123443')   // Fill slug
            ->type(l::IdOrName('bank_beneficiary_address1'), 'abc123443')   // Fill slug
            ->type(l::IdOrName('bank_beneficiary_address2'), 'abc123443')   // Fill slug
            ->type(l::IdOrName('bank_beneficiary_address3'), 'abc123443')
            ->type(l::IdOrName('bank_beneficiary_city'), 'jaipur')
            ->type(l::IdOrName('bank_beneficiary_state'), 'RJ')
            ->type(l::IdOrName('bank_beneficiary_pin'), '123443')
            ->click(l::css('form[name="step4"] > fieldset > .prev-next > .btn-save'))
            ->waitForPresent('form[name="step4"] > fieldset > .alerts > .alert-success');

        $this->assertFalse($this->browser->isElementPresent(l::css('form[name=\"step4\"] > fieldset > .alerts > .alert-danger')));

        // Upload documents and save
        // S3 API is mocked in selenium/init.php to avoid requests to AWS
        $this->browser
            ->click(l::css('form[name="step4"] > fieldset > .prev-next > .btn-next'))
            ->waitForVisible('form[name="step5"]')
            ->attachFile(l::IdOrName('business_proof'), URL::to('/img/logo.png'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step5\"] > fieldset > div > div > .alerts > .alert-success').length == 1", 20000)
            ->attachFile(l::IdOrName('business_pan_proof'), URL::to('/img/logo.png'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step5\"] > fieldset > div > div > .alerts > .alert-success').length == 2", 20000)
            ->attachFile(l::IdOrName('address_proof'), URL::to('/img/logo.png'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step5\"] > fieldset > div > div > .alerts > .alert-success').length == 3", 20000)
            ->attachFile(l::IdOrName('promoter_address_proof'), URL::to('/img/logo.png'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step5\"] > fieldset > div > div > .alerts > .alert-success').length == 4", 20000)
            ->click(l::css('form[name="step5"] > fieldset > .prev-next > .btn-save'))
            ->waitForPresent('form[name="step5"] > fieldset > .alerts > .alert-success');

        $this->assertFalse($this->browser->isElementPresent(l::css('form[name=\"step4\"] > fieldset > .alerts > .alert-danger')));

        // Submit for activation
        $this->browser
            ->click(l::css('form[name="step5"] > fieldset > .prev-next > .btn-next'))
            ->waitForVisible('form[name="step6"]')
            ->waitForLoaded()
            ->click(l::IdOrName('agree_terms'))
            ->click(l::css('.btn-submit'));

            // ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step6\"] > fieldset > .alerts > .alert-success')
            // ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.activation-wrapper > .alerts > .alert-info');

        $this->assertFalse($this->browser->isElementPresent(l::css('form[name=\"step6\"] > fieldset > .alerts > .alert-danger')));
    }

    /**
     * Tests Profile Panel Display
     */
    public function testProfilePanel()
    {
        // Testing profile display
        $this->browser
            ->waitAndClickById('profileNav')
            ->waitForPresent('.profile-wrapper')
            ->waitForLoaded();

        $this->assertBodyHasText($this->merchant->name);

        $this->assertBodyHasText($this->merchant->id);

        // Test Change Password
        $this->browser
            ->click(l::css('.btn-change-pwd'))
            ->waitForPresent('.change-pwd-modal')
            ->type(l::IdOrName('old_password'), '123456xx')
            ->type(l::IdOrName('password'), '1234567xx')
            ->type(l::IdOrName('password_confirmation'), '1234567xx')
            ->click(l::css('.modal-ok'))
            // Click in the button
            ->waitForAbsent('.change-pwd-modal')
            ->waitForLoaded();

        $this->assertBodyHasText("Password changed successfully");
    }

    public function testInvitations()
    {
        //
    }

    /**
     * Tests merchant logout since he is logged in after confirmation
     */
    public function testLogout()
    {
        $this->browser
            ->waitForPresent('.user-dropdown')
            ->click(l::css('.user-dropdown'))
            ->click(l::linkContaining('Logout'))
            ->waitForPresent('form[name="signin"]');
    }
}
