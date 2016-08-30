<?php
namespace Tests\Integration;

use App\Merchant;
use App\User;
use App\Invitation;
use Laracasts\TestDummy\Factory;
use Models;
use Selenium\Locator as l;
use URL;
use App\Lib\Util;
use Uuid;
use PHPUnit_Extensions_Selenium2TestCase_Keys as Keys;
use Facebook\WebDriver\JavascriptExecutor;

class MerchantTest extends TestCase
{
    const TEAM_USER_EMAIL = 'testTeamUser@razorpay.com';

    protected static $migrated = false;

    protected static $setUp = false;

    protected static $user = null;

    protected static $merchant = null;

    public function setUp()
    {
        parent::setUp();

        if (self::$setUp === true)
        {
            self::$merchant = Merchant\Entity::firstorfail();
            self::$user = User\Entity::firstorfail();
            return;
        }

        Factory::$factoriesPath = __DIR__.'/../factories/';

        if (static::$migrated === false)
        {
            // Truncates all tables befor first test
            $this->truncateAll();
            static::$migrated = true;
        }

        // Creates a new merchant if none exists in db otherwise uses that. This is necessary for persisting sessions between tests
        $businessName = Util::random_alpha_string(10);
        try
        {
            self::$merchant = Merchant\Entity::firstorfail();
            self::$user = User\Entity::firstorfail();
        }
        catch(\Exception $e)
        {
            self::$merchant = $this->buildEntity('merchant', array(
                'id'    => Uuid::generate(),
                'email' => static::generateMerchantEmail(),
                'name'  => $businessName
            ));
            self::$user = $this->buildEntity('user', array(
                'id'    => Uuid::generate(),
                'email' => static::generateMerchantEmail(),
                'name'  => $businessName
            ));
        }

        // Make sure that the merchant has webhook tagged
        // So the webhook button is visible
        self::$merchant->tag('webhooks', 'team', 'orders');

        self::$setUp = true;
    }

    public function setUpPage()
    {
        $this->timeouts()->implicitWait(10000);

        $this->url('#/access/signin');
        $this->waitUntilDisplayedByXPath('form','name','signin');
        $this->setValueByName('email', self::$merchant->email);
        $this->setValueByName('password', '123456xx');
    }

    /**
     * Tests merchant registration
     */
    public function testRegister()
    {
        $businessName = Util::random_alpha_string(6). ' Merchant';

        $this->url('#/access/signup');
        $this->waitUntilDisplayedByXPath('form','name','signup');
        $this->execScript('$("input[name=\"agree\"]").click()');
        $this->setValueByName('business_name', $businessName);
        $this->setValueByName('name', self::$merchant->name);
        $this->setValueByName('email', self::$merchant->email);
        $this->setValueByName('contact_mobile', '9999999999');
        $this->setValueByName('password', '123456xx');
        $this->setValueByName('password_confirmation', '123456xx');

        $this->clickByXPath('button','name','submit');
        $this->waitUntilDisplayedByCss('div.alert-success');
        $this->waitUntilContainsByCss('body', 'Please check your inbox for confirmation email from Razorpay');
    }

    /**
     * Tests merchant confirmation
     */
    public function testUserConfirmation()
    {
        $confirm_token = self::$user->confirm_token;
        $this->url('#/access/confirm/'.$confirm_token);
        $this->waitUntilDisplayedByClassName('alert-success');
        $this->waitUntilContainsByCss('body', 'Confirmation successful');
    }

    /**
     * Tests merchant login
     */
    public function testLogin()
    {
        $this->url('#/access/signin');
        $this->waitUntilDisplayedByXPath('form','name','signin');
        $this->setValueByName('email', self::$merchant->email);
        $this->setValueByName('password', '123456xx');
        $this->clickByName('submit');
        $this->waitUntilDisplayedByClassName('navbar');
        $this->waitUntilContainsByCss('body', 'Welcome to Razorpay');
        $this->waitUntilContainsByCss('body', 'Total Payments');
        $this->waitUntilContainsByCss('body', 'Successful Transactions');
    }

    public function testAddTeamMember()
    {
        $teamUser = $this->buildEntity('user', array(
            'id'    => Uuid::generate(),
            'email' => self::TEAM_USER_EMAIL,
            'name'  => 'kdfksdfd'
        ));
        $this->clickByName('submit');
        $this->waitUntilDisplayedById('manageTeamNav');
        $this->clickById('manageTeamNav');
        $this->waitUntilDisplayedByClassName('invites-table');
        $this->waitUntilContainsByCss('body', 'Invite users to your Organization Team');

        $this->setValueById('description', $teamUser->email);
        $this->selectByNameAndLabel('role', 'Finance');
        $this->clickByXPath('button','text','Send Invitation');
        $this->waitUntilContainsByCss('body', 'Invitation has been successfully sent to '.$teamUser->email);
    }

    public function testAcceptInvitation()
    {
        $invite = Invitation\Entity::firstorfail();
        $this->url('#/access/signup?invitation='.$invite->token);
        $name = Util::random_alpha_string(6);
        $this->setValueByName('name', $name);
        $this->setValueByName('password', '12345xx');
        $this->setValueByName('password_confirmation', '12345xx');
        $this->execScript('$(".agree").click()');
        $this->clickByXPath('button','text','Sign up');
        $this->waitUntilContainsByCss('body', 'Welcome to Razorpay');
    }

    public function testRestrictedAccessRole()
    {
        $this->url('#/access/signin');
        $this->waitUntilDisplayedByXPath('form','name','signin');
        $this->setValueByName('email', self::TEAM_USER_EMAIL);
        $this->setValueByName('password', '12345xx');
        $this->clickByName('submit');
        $this->waitUntilDisplayedByClassName('navbar');
        $this->waitUntilAbsentById('manageTeamNav');
    }

    public function testWebhooks()
    {
        $this->clickByName('submit');
        $this->waitUntilDisplayedById('webhookNav');
        $this->clickById('webhookNav');
        $this->waitUntilDisplayedByClassName('webhooks-table');
        $this->waitUntilContainsByCss('body', 'Webhooks');

        // Testing create wevhook

        $this->clickById('createWebook');
        $this->waitUntilDisplayedByClassName('new-webhook-modal');
        $this->waitUntilContainsByCss('body', 'New Webhook');

        // Check if display includes new webhook
        $this->setValueById('new_webhook_url', 'http://googleeee.com');
        $this->clickByClassName('modal-ok');
        $this->waitUntilAbsentByClassName('new-webhook-modal');
        $this->waitUntilContainsByCss('body', 'Webhook added');
        $this->waitUntilContainsByCss('body', 'http://googleeee.com');
    }

    /**
     * Tests payments panel display
     */

    public function testPaymentsList()
    {
        $this->url('#/access/signin');
        $this->waitUntilDisplayedByXPath('form', 'name', 'signin');
        $this->setValueByName('email', self::$merchant->email);
        $this->setValueByName('password', '123456xx');
        $this->clickByName('submit');
        $this->waitUntilDisplayedById('paymentsNav');
        $this->clickById('paymentsNav');
        $this->waitUntilDisplayedByClassName('payments-table');
        $this->waitUntilContainsByCss('body', 'List of all payments');
    }

    /**
     * Tests refunds panel display
     */
    public function testRefundsPanel()
    {
        $this->clickByName('submit');
        $this->waitUntilDisplayedById('refundsNav');
        $this->clickById('refundsNav');
        $this->waitUntilDisplayedByClassName('refunds-table');
        $this->waitUntilContainsByCss('body', 'List of all refunds');
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
        $this->clickByName('submit');
        $this->waitUntilDisplayedById('keysNav');
        $this->execScript('$("#keysNav").click()');
        $this->waitUntilDisplayedByClassName('keys-table');
        $this->waitUntilContainsByCss('body', 'API Keys');
        $this->waitUntilContainsByCss('body', 'Key Id');

        // Testing Generate new Key
        $this->clickById('generateKey');
        $this->waitUntilDisplayedByClassName('new-key-modal');
        $this->waitUntilContainsByCss('body', 'New Key');
        $this->waitUntilContainsByCss('body', 'Key Generated');

        // New Key generated close the modal
        $this->waitAndClickById('new_keys_ok');
        $this->waitUntilDisplayedByClassName('modal-content');
        $this->clickByClassName('confirm-ok');

        // Now roll the key
        $this->waitAndClickByClassName('roll_key');
        // De-activation choose screen
        $this->assertTrue($this->displayedByClassName('roll-key-modal'));
        $this->waitAndClickByClassName('btn-roll-key-ok');
    }

    /**
     * Tests activation panel display and form filling
     */
    public function testActivationPanel()
    {
        // Testing keys display
        $this->clickByName('submit');
        $this->waitUntilDisplayedById('activationNav');
        $this->clickById('activationNav');

        $this->waitUntilContainsByCss('body', 'Contact Details');

        // Fill in Contact details and save
        $this->clickByXPath('button','text','Save');
        $this->waitUntilDisplayedByClassName('alert-danger');
        $this->setValueByName('contact_name', 'Yo name');
        $this->setValueByName('contact_email', 'email@umail.com');
        $this->setValueByName('transaction_report_email', 'billing@umail.com');
        $this->setValueByName('contact_mobile', '9199192999');
        $this->setValueByName('contact_landline', '9191929393');
        $this->execScript('$(".btn-save")[0].click()');
        // $this->waitUntilDisplayedByClassName('alert-success');

        // Fill in business Details and save
        $this->execScript('$(".btn-next")[0].click()');
        $this->waitUntilDisplayedByXPath('form','name','step2');
        $this->selectByNameAndLabel('business_type', 'Partnership');
        $this->setValueByName('business_name', 'Test Company Pvt Ltd');
        $this->setValueByName('business_dba', 'Tester');
        $this->selectByNameAndLabel('business_international', 'Yes');
        $this->selectByNameAndValue('business_paymentdetails', 'B2B');
        $this->setValueByName('business_registered_address', 'address 1, 2');
        $this->setValueByName('business_registered_state', 'state');
        $this->setValueByName('business_registered_city', 'city');
        $this->setValueByName('business_registered_pin', '333333');
        $this->setValueByName('business_operation_address', 'address 1, 2');
        $this->setValueByName('business_operation_state', 'state');
        $this->setValueByName('business_operation_city', 'city');
        $this->setValueByName('business_operation_pin', '333333');
        //$this->byName('or_same')->click();
        $this->setValueByName('business_doe', '1990-11-01');
        $this->setValueByName('company_cin', 'cin123455');
        $this->setValueByName('company_pan', 'pan12345');
        $this->setValueByName('company_pan_name', 'TEST COMPANY (OPC)');
        $this->setValueByName('business_model', 'my model');
        $this->selectByNameAndLabel('transaction_volume', '1 to 10 lakh');
        $this->setValueByName('transaction_value', '120');
        $this->setValueByName('promoter_pan', 'PAE123');
        $this->setValueByName('promoter_pan_name', 'Promoter');
        $this->execScript('$(".btn-save")[1].click()');
        //$this->waitUntilDisplayedByClassName('alert-success');

        $this->execScript('$(".btn-next")[1].click()');
        $this->waitUntilDisplayedByXPath('form','name','step3');
        $this->setValueByName('business_website', 'http://testing.com');
        $this->setValueByName('website_about', 'http://testing.com');
        $this->setValueByName('website_contact', 'http://testing.com');
        $this->setValueByName('website_privacy', 'http://testing.com');
        $this->setValueByName('website_terms', 'http://testing.com');
        $this->setValueByName('website_refund', 'http://testing.com');
        $this->setValueByName('website_pricing', 'http://testing.com');
        $this->execScript('$(".btn-save")[2].click()');
        //$this->waitUntilDisplayedByClassName('alert-success');

        // Fill in Bank Account Details and save
        $this->execScript('$(".btn-next")[2].click()');
        $this->waitUntilDisplayedByXPath('form','name','step4');
        $this->setValueByName('bank_account_number', 'RZP123443');
        $this->setValueByName('bank_account_number_confirmation', 'RZP123443');
        $this->setValueByName('bank_account_name', 'Tester');
        $this->setValueByName('bank_account_type', 'savings');
        $this->setValueByName('bank_branch_ifsc', 'KKBK0000999');
        $this->setValueByName('bank_beneficiary_address1', 'abc123443');
        $this->setValueByName('bank_beneficiary_address2', 'abc123443');
        $this->setValueByName('bank_beneficiary_address3', 'abc123443');
        $this->setValueByName('bank_beneficiary_city', 'jaipur');
        $this->selectByNameAndValue('bank_beneficiary_state', 'RJ');
        $this->setValueByName('bank_beneficiary_pin', '123443');
        $this->execScript('$(".btn-save")[3].click()');
        //$this->waitUntilDisplayedByClassName('alert-danger');
        $this->setValueByName('bank_branch_ifsc', 'KKBK0000261');
        $this->execScript('$(".btn-save")[3].click()');
        //$this->waitUntilDisplayedByClassName('alert-success');

        // Upload documents and save
        // S3 API is mocked in selenium/init.php to avoid requests to AWS
        $this->execScript('$(".btn-next")[3].click()');
        $this->waitUntilDisplayedByXPath('form','name','step5');
        $remote_file = $this->file(__DIR__.'/upload.png');
        $this->byName('business_proof')
            ->value($remote_file);
        $this->waitUntilContainsByCss('body', 'File Uploaded Successfully');
        $this->byName('business_pan_proof')
            ->value($remote_file);
        $this->waitUntilContainsByCss('body', 'File Uploaded Successfully');
        $this->byName('address_proof')
            ->value($remote_file);
        $this->waitUntilContainsByCss('body', 'File Uploaded Successfully');
        $this->byName('promoter_address_proof')
            ->value($remote_file);
        $this->waitUntilContainsByCss('body', 'File Uploaded Successfully');

        $this->execScript('$(".btn-save")[4].click()');
        //$this->waitUntilDisplayedByClassName('alert-success');

        // Submit for activation
        $this->execScript('$(".btn-next")[4].click()');
        $this->waitUntilDisplayedByXPath('form','name','step6');
        $this->waitUntil(function() {
            $this->execScript('$("input[name=\"agree_terms\"]").click()');
            return true;
        }, 20000);
        $this->clickByClassName('btn-submit');
        $this->clickByClassName('btn-submit');  //remove after resolving creevey
        $this->waitUntilContainsByCss('body', 'Form submitted Successfully!');
    }

    /**
     * Tests Profile Panel Display
     */
    public function testProfilePanel()
    {
        // Testing profile display
        $this->clickByName('submit');
        $this->waitUntilDisplayedById('profileNav');
        $this->clickById('profileNav');
        $this->waitUntilDisplayedByClassName('profile-wrapper');

        $this->waitUntilContainsByCss('body', ucfirst(self::$merchant->name));
        $this->waitUntilContainsByCss('body', self::$merchant->id);

        // Test Change Password
        $this->clickByClassName('btn-change-pwd');
        $this->waitUntilDisplayedByClassName('change-pwd-modal');
        $this->setValueByName('old_password', '123456xx');
        $this->setValueByName('password', '1234567xx');
        $this->setValueByName('password_confirmation', '1234567xx');
        $this->clickByClassName('modal-ok');
        $this->waitUntilAbsentByClassName('change-pwd-modal');
        $this->waitUntilContainsByCss('body', 'Password changed successfully.');
    }

    /**
     * Tests merchant logout since he is logged in after confirmation
     */
    public function testLogout()
    {
        $this->setValueByName('email', self::$merchant->email);
        $this->setValueByName('password', '1234567xx');
        $this->clickByName('submit');
        $this->waitUntilDisplayedByClassName('user-dropdown');
        $this->clickByClassName('user-dropdown');
        $this->execScript('$("a:contains(\"Logout\")").click()');
        $this->waitUntilDisplayedByXPath('form','name','signin');
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->closeWindow();
    }
}
