<?php
namespace Tests\Integration;

use Selenium\Locator as l;
use Models;
use URL;
use Exception;

class AdminTest extends TestCase
{
    protected $admin;

    protected $merchant;

    protected $merchant_details;

    protected static $migrated = false;

    public function setUp()
    {
        parent::setUp();

        if (static::$migrated === false)
        {
            // Truncates all tables befor first test
            $this->truncateAll();
            static::$migrated = true;
        }

        /** Creates a new admin & merchant if none exist in db, else uses first admin. This is necesssary to persist sessions between tests **/
        try
        {
            $this->admin = Models\Admin\Entity::firstorfail();
            $this->merchant_details = Models\MerchantDetails\Entity::firstorfail();
            $this->merchant = $this->merchant_details->merchant;

        }
        catch(Exception $e)
        {
            $this->admin = $this->createEntity('admin');
            $this->merchant = $this->createEntity('merchant', array('id'=>\Models\Merchant\Entity::generateUniqueId(), 'email' =>static::generateMerchantEmail(), 'confirm_token' => static::generateRandomString(24)));
            $this->merchant_details = $this->createEntity('merchant_details', array('merchant_id'=>$this->merchant->id));
            $error = (new Models\Merchant\Service)->confirm($this->merchant->confirm_token);

            if (empty($error) === false)
            {
                $this->fail($error[0]);
            }
        }
    }

    /**
     * Tests admin login
     */
    public function testLogin()
    {
        $this->browser
            ->open(URL::to('/admin#/access/signin'))    // Visits login page
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"signin\"]').length > 0", 20000)
            ->type(l::IdOrName('username'), $this->admin->username)   // Fill username
            ->type(l::IdOrName('password'), '123456')   // Fill password
            ->click(l::IdOrName('submit'))                 // Click in the button
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.navbar').length > 0", 20000);

        $this->assertBodyHasText("Pending Activations");
    }

    /**
     * Tests Pricing Module of admin
     */
    public function testPricing()
    {
        // Check opening of pricing page from dashboard
        $this->browser
            ->open(URL::to('/admin#'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#pricingNav').length > 0", 20000)
            ->click(l::linkContaining('Pricing Plans'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.pricing-table').length > 0", 20000);                      // Wait for page to load

        $this->assertBodyHasText("List of all Plans");


        // Tests creation of new plan
        $this->browser
            ->click(l::linkContaining('Create New Pricing Plan'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#create-plan-panel').is(':visible')", 20000)
            ->type(l::IdOrName('plan_name'), static::generateRandomString(7))
            ->select(l::IdOrName('payment_mode'), 'Card')
            ->select(l::IdOrName('payment_mode_type'), 'Credit')
            ->type(l::IdOrName('percent_rate'), '280')
            ->type(l::IdOrName('fixed_rate'), '200')
            ->click(l::linkContaining('Save and Add More Rules'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.alert-success').length > 0", 20000);

        $this->assertBodyHasText("Plan created successfully");

        // Tests Creation of new rule
        $this->browser
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#show-plan-panel').is(':visible')", 20000)
            ->select(l::IdOrName('payment_mode_type'), 'Debit')
            ->select(l::IdOrName('payment_mode'), 'Card')
            ->type(l::IdOrName('percent_rate'), '280')
            ->type(l::IdOrName('fixed_rate'), '200')
            ->click(l::linkContaining('Save'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.butterbar.hide').length > 1", 20000);

        $this->assertBodyHasText("Rule added successfully");
    }

    /**
     * Tests merchant listing for admin
     */
    public function testMerchantsList()
    {
        $this->browser
            ->open(URL::to('/admin#'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#merchantsNav').length > 0", 20000)
            ->click(l::linkContaining('Merchants'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.merchants-table').length > 0", 20000);

        $this->assertBodyHasText($this->merchant->id);
        $this->assertBodyHasText($this->merchant->email);
    }

    /**
     * Tests merchant details management for admin
     */
    public function testMerchantDetails()
    {
        // Get merchant details & actions
        $this->browser
            ->open(URL::to('/admin#/app/merchants/list'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.merchants-table > tbody > tr > td').length > 5", 20000)
            ->click(l::linkContaining($this->merchant->id))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.merchant-wrapper').length > 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.butterbar.hide').length == 2", 20000);

        $this->assertBodyHasText($this->merchant->id);
        $this->assertBodyHasText("Merchant Detail");

        // Lock Activation Form
        $this->browser
            ->click(l::linkContaining('Lock Activation Form'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.alert-success').length > 0", 20000);

        $this->assertBodyHasText('Merchant Form locked successfully');

        // Unlock Activation Form
        $this->browser
            ->click(l::linkContaining('Unlock Activation Form'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.butterbar.hide').length == 2", 20000);

        $this->assertBodyHasText('Merchant Form unlocked successfully');

        // Assign Pricing
        $this->browser
            ->click(l::linkContaining('Assign Pricing'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.pricing-modal').length > 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('[name=\"pricing_plan_id\"] > option').length > 1", 20000)
            ->select(l::IdOrName('pricing_plan_id'), 'index=1')
            ->click(l::css('.modal-ok'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.confirm-modal').length > 0", 20000)
            ->click(l::css('.confirm-ok'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.pricing-modal').length == 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.butterbar.hide').length == 2", 20000);

        $this->assertFalse($this->browser->isElementPresent(l::css('.alert-danger')));

        $this->assertBodyHasText('Plan Assigned successfully');

        // Assign Terminal
        $this->browser
            ->click(l::linkContaining('Assign Terminal'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.terminal-modal').length > 0", 20000)
            ->type(l::IdOrName('gateway_merchant_id'), static::generateRandomString(10))
            ->type(l::IdOrName('gateway_terminal_id'), static::generateRandomString(10))
            ->type(l::IdOrName('gateway_terminal_password'), 'testing')
            ->type(l::IdOrName('gateway_terminal_password_confirmation'), 'testing')
            ->click(l::css('.modal-ok'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.confirm-modal').length > 0", 20000)
            ->click(l::css('.confirm-ok'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.terminal-modal').length == 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.butterbar.hide').length == 2", 20000);

        $this->assertFalse($this->browser->isElementPresent(l::css('.alert-danger')));

        $this->assertBodyHasText('Terminal Assigned successfully');

        // Activate Merchant
        $this->browser
            ->click(l::linkContaining('Activate Merchant'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.confirm-ok').length > 0", 20000)
            ->click(l::css('.confirm-ok'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.confirm-modal').length == 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.butterbar.hide').length == 2", 20000);

        $this->assertFalse($this->browser->isElementPresent(l::css('.alert-danger')));

        $this->assertBodyHasText('Merchant Activated successfully');

        // See Merchant Activation Details
        $this->browser
            ->click(l::linkContaining('See Activation Form Details'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.activation-wrapper').length > 0", 20000);

    }

    /**
     * Tests login as merchant for admin
     */
    public function testLoginAsMerchant()
    {
        $this->browser
            ->open(URL::to('/admin#/app/merchants/'.$this->merchant->id.'/detail'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.butterbar.hide').length == 2", 20000);

        $loginAsMerchantLink = $this->browser->getAttribute('link=Login as Merchant@href');

        $this->browser
            ->open(URL::to($loginAsMerchantLink))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.navbar').length > 0", 20000);                     // Wait for page to load

        $this->assertBodyHasText("Welcome to Razorpay");

        $this->browser
                ->open(URL::to('/user/logout'))
                ->waitForPageToLoad(20000);
    }

    /**
     * Tests checking of merchant activation details by admin
     */
    public function testMerchantActivationDetails()
    {

        // Browsing the whole form
        $this->browser
            ->open(URL::to('/admin#/app/merchants/'.$this->merchant->id.'/activation'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.activation-wrapper').length > 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step1\"]').is(':visible')", 20000)
            ->click(l::css('form[name="step1"] > .prev-next > .btn-next'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step2\"]').is(':visible')", 20000)
            ->click(l::css('form[name="step2"] > .prev-next > .btn-next'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step3\"]').is(':visible')", 20000)
            ->click(l::css('form[name="step3"] > .prev-next > .btn-next'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step4\"]').is(':visible')", 20000)
            ->click(l::css('form[name="step4"] > .prev-next > .btn-next'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step5\"]').is(':visible')", 20000)
            ->click(l::css('form[name="step5"] > .prev-next > .btn-next'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step6\"]').is(':visible')", 20000);
    }

    public function testMerchantLiveEnableDisable()
    {
        $this->browser
            ->open(URL::to('/admin#/app/merchants/'.$this->merchant->id.'/detail'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.merchant-wrapper').length > 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.butterbar.hide').length > 1", 20000)
            ->click(l::linkContaining('Disable Live Transactions'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.confirm-ok').length > 0", 20000)
            ->click(l::css('.confirm-ok'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.confirm-modal').length == 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.butterbar.hide').length == 2", 20000);

        $this->assertFalse($this->browser->isElementPresent(l::css('.alert-danger')));

        $this->assertBodyHasText('Live transactions for merchant disabled successfully');

        $this->browser
            ->click(l::linkContaining('Enable Live Transactions'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.butterbar.hide').length == 2", 20000);

        $this->assertFalse($this->browser->isElementPresent(l::css('.alert-danger')));

        $this->assertBodyHasText('Live transactions for merchant enabled successfully');
    }

    /**
     * Tests Admins Mangement
     */
    public function testManageAdmins()
    {
        // Testing admins display
        $this->browser
            ->open(URL::to('/admin#'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#adminsNav').length > 0", 20000)
            ->click(l::IdOrName('adminsNav'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.admins-table > tbody > tr').length > 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.butterbar.hide').length == 2", 20000);

        $this->assertBodyHasText($this->admin->name);

        $this->assertBodyHasText($this->admin->username);

        // Test Add Admin
        $this->browser
            ->click(l::linkContaining('Add new Admin'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.new-admin-modal').length > 0", 20000)
            ->type(l::IdOrName('name'), 'Tester')
            ->type(l::IdOrName('username'), static::generateRandomString(7))
            ->type(l::IdOrName('email'), static::generateMerchantEmail())
            ->type(l::IdOrName('password'), '1234567')
            ->type(l::IdOrName('password_confirmation'), '1234567')
            ->click(l::css('.modal-ok'))                 // Click in the button
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.new-admin-modal').length == 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.butterbar.hide').length == 2", 20000);

        $this->assertBodyHasText("Admin created successfully");
    }

    /**
     * Tests Profile Panel Display
     */
    public function testProfilePanel()
    {
        // Testing profile display
        $this->browser
            ->open(URL::to('/admin#'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#profileNav').length > 0", 20000)
            ->click(l::IdOrName('profileNav'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.profile-wrapper').length > 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.butterbar.hide').length == 2", 20000);

        $this->assertBodyHasText($this->admin->name);

        $this->assertBodyHasText($this->admin->username);

        // Test Change Password
        $this->browser
            ->click(l::css('.btn-change-pwd'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.change-pwd-modal').length > 0", 20000)
            ->type(l::IdOrName('old_password'), '123456')
            ->type(l::IdOrName('password'), '1234567')
            ->type(l::IdOrName('password_confirmation'), '1234567')
            ->click(l::css('.modal-ok'))                 // Click in the button
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.change-pwd-modal').length == 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.butterbar.hide').length == 2", 20000);

        $this->assertBodyHasText("Password changed successfully");
    }

    /**
     * Tests admin logout
     */
    public function testLogout()
    {
        $this->browser
            ->open(URL::to('/admin'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.user-dropdown').length > 0", 20000)
            ->click(l::css('.user-dropdown'))
            ->click(l::linkContaining('Logout'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"signin\"]').length > 0", 20000);
    }
}