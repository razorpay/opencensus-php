<?php

use Selenium\Locator as l;

class AdminTest extends IntegrationTestCase
{   
    protected $admin;

    protected $merchant;

    protected $merchant_details;

    protected static $migrated = false;

    public function setUp()
    {   
        parent::setUp();

        if(static::$migrated === false)
        {   
            //Truncates all tables befor first test
            $this->truncateAll();
            static::$migrated = true;
        }

        /** Creates a new admin & merchant if none exist in db, else uses first admin. This is necesssary to persist sessions between tests **/
        try
        {
            $this->admin = Models\DAL\Admin::firstorfail();
            $this->merchant_details = Models\DAL\MerchantDetails::firstorfail();
            $this->merchant = $this->merchant_details->merchant;

        }
        catch(Exception $e)
        {
            $this->admin = $this->createEntity('admin');
            $this->merchant = $this->createEntity('merchant', array('id'=>static::generateRandomString(24), 'email' =>static::generateMerchantEmail(), 'confirm_token' => static::generateRandomString(24)));
            $this->merchant_details = $this->createEntity('merchant_details', array('merchant_id'=>$this->merchant->id));
            
            if((new Models\Service\Merchant)->confirm($this->merchant->confirm_token) === false)
            $this->fail('Failure in merchant activation, check merchant tests to ensure it is working');
        }
    }

    /**
     * Tests admin login
     */
    public function testLogin()
    {
        $this->browser
            ->open(URL::action('AdminController@getLogin'))    // Visits login page
            ->type(l::IdOrName('username'), $this->admin->username)   // Fill username
            ->type(l::IdOrName('password'), '123456')   // Fill password
            ->click(l::css('#form-button'))                 // Click in the button
            ->waitForPageToLoad(2000);                      // Wait for page to load

        // Asserts if at the end the user is at the index
        $this->assertEquals(URL::action('AdminController@getIndex'),$this->browser->getLocation());

        $this->browser
            ->click(l::linkContaining('Manage Merchant'))
            ->waitForPageToLoad(2000);

        $this->assertBodyHasText($this->merchant->id);
    }

    /**
     * Tests Pricing Module of admin
     */
    public function testPricing()
    {   
        // Check opening of pricing page from dashboard
        $this->browser
            ->open(URL::action('AdminController@getIndex'))
            ->click(l::linkContaining('Pricing Plans'))   
            ->waitForPageToLoad(2000);                      // Wait for page to load

        $this->assertEquals(URL::action('AdminController@getPricingList'),$this->browser->getLocation());


        //Tests creation of new plan
        $this->browser
            ->open(URL::action('AdminController@getPricingList'))
            ->waitForPageToLoad(2000)
            ->click(l::linkContaining('Create new plan'))   
            ->waitForPageToLoad(2000)
            ->type(l::IdOrName('plan_name'), static::generateRandomString(7))
            ->select(l::IdOrName('payment_mode'), 'Card')
            ->select(l::IdOrName('payment_mode_type'), 'Credit')
            ->type(l::IdOrName('percent_rate'), '280')
            ->type(l::IdOrName('fixed_rate'), '200')
            ->click(l::css('.btn-primary'))
            ->waitForPageToLoad(2000);

        $this->assertBodyHasText("Plan Added successfully");

        //Tests Creation of new rule
        $this->browser
            ->select(l::IdOrName('payment_mode'), 'Card')
            ->select(l::IdOrName('payment_mode_type'), 'Credit')
            ->select(l::IdOrName('payment_issuer'), 'HDFC')            
            ->type(l::IdOrName('percent_rate'), '280')
            ->type(l::IdOrName('fixed_rate'), '200')
            ->click(l::css('.btn-primary'))
            ->waitForPageToLoad(2000);

        $this->assertBodyHasText("Rule Added successfully");

        //Check Opening of pricing rules from price list
        $this->browser
            ->open(URL::action('AdminController@getPricingList'))
            ->waitForPageToLoad(2000)
            ->click(l::linkContaining('View/Edit Plan Rules'))   
            ->waitForPageToLoad(2000);

        $this->assertBodyHasText("Plan Name:");
    }

    /**
     * Tests merchant listing for admin
     */
    public function testMerchants()
    {
        $this->browser
            ->open(URL::action('AdminController@getIndex'))
            ->click(l::linkContaining('Merchants'))   
            ->waitForPageToLoad(2000);                      // Wait for page to load

        $this->assertEquals(URL::action('AdminController@getMerchantList'),$this->browser->getLocation());

        $this->assertBodyHasText($this->merchant->id);
        $this->assertBodyHasText($this->merchant->email);
    }
    
    /**
     * Tests login as merchant for admin
     */
    public function testLoginAsMerchant()
    {   
        $this->browser
            ->open(URL::action('AdminController@getMerchantList'))
            ->waitForPageToLoad(2000);

        $loginAsMerchantLink = $this->browser->getAttribute('link=Login as Merchant@href');

        $this->browser
            ->open(URL::to($loginAsMerchantLink))
            ->waitForPageToLoad(2000);

        $this->assertEquals(URL::action('MerchantController@getIndex').'/#!/',$this->browser->getLocation());
    }

    /**
     * Tests checking merchnat status and locking/unlocking merchant form
     */
    public function testMerchantStatus()
    {   
        $this->browser
            ->open(URL::action('AdminController@getMerchantList'))
            ->waitForPageToLoad(2000)
            ->click(l::linkContaining('Manage Merchant'))
            ->waitForPageToLoad(2000);

        $this->assertBodyHasText($this->merchant->id);

        $this->assertEquals(URL::action('AdminController@getMerchant', $this->merchant->id),$this->browser->getLocation());

        $this->browser
            ->click(l::linkContaining('Lock Form for user'))
            ->waitForPageToLoad(2000);

        $this->assertBodyHasText('Merchant activation form locked Successfully!');

        $this->browser
            ->click(l::linkContaining('Unlock Form for user'))
            ->waitForPageToLoad(2000);

        $this->assertBodyHasText('Merchant activation form unlocked Successfully!');

        $this->browser
            ->click(l::linkContaining('Check Activation Form Details'))
            ->waitForPageToLoad(2000);

        $this->assertEquals(URL::to('/admin/merchant/'.$this->merchant->id.'/details'),$this->browser->getLocation());
    }

    public function testAddMerchantPricing()
    {   
        // Check opening of pricing page from dashboard
        $this->browser
            ->open(URL::action('AdminController@getMerchant', $this->merchant->id))
            ->click(l::linkContaining('Modify Pricing Plan'))   
            ->waitForPageToLoad(2000);                      // Wait for page to load

        $this->assertEquals(URL::action('AdminController@getMerchantPricing', $this->merchant->id),$this->browser->getLocation());

        $this->browser
            ->select(l::IdOrName('pricing_plan_id'), 'index=1')
            ->click(l::css('.btn-primary'))
            ->waitForPageToLoad(2000);

        $this->assertBodyHasText('Pricing added successfully');
    }

    public function testAddMerchantTerminal()
    {   
        // Check opening of pricing page from dashboard
        $this->browser
            ->open(URL::action('AdminController@getMerchant', $this->merchant->id))
            ->click(l::linkContaining('Add Terminal'))   
            ->waitForPageToLoad(2000);                      // Wait for page to load

        $this->assertEquals(URL::action('AdminController@getMerchantTerminal', $this->merchant->id),$this->browser->getLocation());

        $this->browser
            ->type(l::IdOrName('gateway_merchant_id'), static::generateRandomString(10))
            ->type(l::IdOrName('gateway_terminal_id'), static::generateRandomString(10))
            ->type(l::IdOrName('gateway_terminal_password'), 'testing')
            ->type(l::IdOrName('gateway_terminal_password_confirmation'), 'testing')
            ->click(l::css('.btn-primary'))
            ->waitForPageToLoad(2000);

        $this->assertBodyHasText('Terminal added successfully');
    }


    /**
     * Tests checking of merchant activation details by admin
     */
    public function testMerchantActivationDetails()
    {   
        
        //Browsing the whole form
        $this->browser
            ->open(URL::to('/admin/merchant/'.$this->merchant->id.'/details'))
            ->waitForPageToLoad(2000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activation-form > fieldset:eq(0)').is(':visible')", 2000)
            ->click(l::css('#activation-form > fieldset:eq(0) > .prev-next > .next'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activation-form > fieldset:eq(1)').is(':visible')", 2000)
            ->click(l::css('#activation-form > fieldset:eq(1) > .prev-next > .next'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activation-form > fieldset:eq(2)').is(':visible')", 2000)
            ->click(l::css('#activation-form > fieldset:eq(2) > .prev-next > .next'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activation-form > fieldset:eq(3)').is(':visible')", 2000)
            ->click(l::css('#activation-form > fieldset:eq(3) > .prev-next > .next'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activation-form > fieldset:eq(4)').is(':visible')", 2000)
            ->click(l::css('#activation-form > fieldset:eq(4) > .prev-next > .next'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activation-form > fieldset:eq(5)').is(':visible')", 2000);
    }

    /**
     * tests activating a new merchant
     */

    public function testMerchantActivation()
    {   
        //Registers merchant in API so that he can be activated
        
        $this->browser
            ->open(URL::to('admin/merchant/'.$this->merchant->id))
            ->waitForPageToLoad(2000)
            ->click(l::linkContaining('Activate Merchant'))
            ->waitForPageToLoad(2000);

        $this->browser->getConfirmation();

        $this->assertBodyHasText('Merchant activated successfully');
    }

    public function testMerchantDeactivation()
    {   
        //Registers merchant in API so that he can be activated
        
        $this->browser
            ->open(URL::to('admin/merchant/'.$this->merchant->id))
            ->waitForPageToLoad(2000)
            ->click(l::linkContaining('Deactivate Merchant'))
            ->waitForPageToLoad(2000);

        $this->browser->getConfirmation();

        $this->assertBodyHasText('Merchant deactivated successfully');
    }
    /**
     * Tests Change Password
     */
    public function testChangePassword()
    {
         $this->browser
            ->open(URL::action('AdminController@getIndex'))
            ->click(l::linkContaining('Change Password'))
            ->waitForPageToLoad(2000)
            ->type(l::IdOrName('old_password'), '123456')
            ->type(l::IdOrName('password'), '1234567')
            ->type(l::IdOrName('password_confirmation'), '1234567')
            ->click(l::css('#form-button'))                 // Click in the button
            ->waitForPageToLoad(2000);

        $this->assertBodyHasText("Password changed successfully");
    }

    /**
     * Tests admin logout
     */
    public function testLogout()
    {
        $this->browser
            ->open(URL::action('AdminController@getIndex'))    // Visits the 'stuff' index
            ->click(l::linkContaining('Sign Out'))                 // Click in the button
            ->waitForPageToLoad(2000);                      // Wait for page to load
        
        // Asserts if at the end the user is at the stuff index again
        $this->assertEquals(URL::action('AdminController@getLogin'),$this->browser->getLocation());
    }
}