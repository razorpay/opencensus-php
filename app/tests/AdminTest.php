<?php

use Selenium\Locator as l;

class AdminTest extends IntegrationTestCase
{   
    protected $admin;

    protected $merchant;

    protected $merchant_details;

    public function setUp()
    {   
        parent::setUp();

        /** Creates a new admin & merchant if none exist in db, else uses first admin. This is necesssary to persist sessions between tests **/
        try
        {
            $this->admin = Models\DAL\Admin::firstorfail();
            $this->merchant_details = Models\DAL\MerchantDetails::firstorfail();

        }
        catch(Exception $e)
        {
            $this->admin = $this->createEntity('admin');
            $this->merchant = $this->createEntity('merchant', array('id'=>static::generateRandomString(24), 'email' =>static::generateMerchantEmail(), 'confirm_token' => static::generateRandomString(24)));
            $this->merchant_details = $this->createEntity('merchant_details', array('merchant_id'=>$this->merchant->id));
        }

        $this->merchant = $this->merchant_details->merchant;
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

        $this->assertBodyHasText("#YOLO");
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

        //Check Opening of pricing rules from price list
        $this->browser
            ->click(l::linkContaining('View/Edit Plan Rules'))   
            ->waitForPageToLoad(2000);

        $this->assertBodyHasText("Plan Name:");

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

        $this->assertEquals(URL::action('AdminController@getMerchants'),$this->browser->getLocation());

        $this->assertBodyHasText($this->merchant->id);
        $this->assertBodyHasText($this->merchant->email);
    }
    
    /**
     * Tests login as merchant for admin
     */
    public function testLoginAsMerchant()
    {   
        $this->browser
            ->open(URL::action('AdminController@getMerchants'))
            ->waitForPageToLoad(2000);

        $loginAsMerchantLink = $this->browser->getAttribute('link=Login as Merchant@href');

        $this->browser
            ->open(URL::to($loginAsMerchantLink))
            ->waitForPageToLoad(2000);

        $this->assertEquals(URL::action('MerchantController@getIndex').'/#!/',$this->browser->getLocation());
    }

    /**
     * Tests chekcing of merchant details by admin
     */
    public function testMerchantDetails()
    {   
        $this->browser
            ->open(URL::action('AdminController@getMerchants'))
            ->waitForPageToLoad(2000)
            ->click(l::linkContaining('Merchant Details'))
            ->waitForPageToLoad(2000);

        $this->assertEquals(URL::to('/admin/merchant/'.$this->merchant->id.'/details'),$this->browser->getLocation());

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
     * Tests checking merchnat status and locking/unlocking merchant form
     */
    public function testMerchantStatus()
    {   
        $this->browser
            ->open(URL::action('AdminController@getMerchants'))
            ->waitForPageToLoad(2000)
            ->click(l::linkContaining('Manage Status'))
            ->waitForPageToLoad(2000);

        $this->assertBodyHasText($this->merchant->id);

        $this->browser
            ->click(l::linkContaining('Lock Form for user'))
            ->waitForPageToLoad(2000);

        $this->assertBodyHasText('Merchant activation form locked Successfully!');

        $this->browser
            ->click(l::linkContaining('Unlock Form for user'))
            ->waitForPageToLoad(2000);

        $this->assertBodyHasText('Merchant activation form unlocked Successfully!');
    }

    /**
     * tests activating a new merchant
     */

    public function testMerchantActivation()
    {   
        //Registers merchant in API so that he can be activated
        if((new Models\Service\Merchant)->confirm($this->merchant->confirm_token) === false)
            $this->fail('Failure in merchant activation, check merchant test to ensure it is working');

        $this->browser
            ->open(URL::to('admin/merchant/'.$this->merchant->id))
            ->waitForPageToLoad(2000)
            ->click(l::linkContaining('Activate Merchant'))
            ->waitForPageToLoad(2000)
            ->type(l::IdOrName('tid'), '12111')
            ->type(l::IdOrName('tid_password'), '123456')
            ->type(l::IdOrName('tid_password_confirmation'), '123456')
            ->select(l::IdOrName('pricing_plan'), 'index=1')
            ->click(l::css('.btn-primary'))
            ->waitForPageToLoad(2000);

        $this->assertBodyHasText('Merchant activated successfully');
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