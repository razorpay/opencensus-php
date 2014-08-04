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

        try
        {
            $this->admin = Models\DAL\Admin::firstorfail();
            $this->merchant_details = Models\DAL\MerchantDetails::firstorfail();

        }
        catch(Exception $e)
        {
            $this->admin = $this->createEntity('admin');
            $this->merchant_details = $this->createEntity('merchant_details');
        }

        $this->merchant = $this->merchant_details->merchant;
    }

    public function testLogin()
    {
        $this->browser
            ->open(URL::action('AdminController@getLogin'))    // Visits the 'stuff' index
            ->type(l::IdOrName('username'), $this->admin->username)   // Fill name
            ->type(l::IdOrName('password'), '123456')   // Fill slug
            ->click(l::css('#form-button'))                 // Click in the button
            ->waitForPageToLoad(2000);                      // Wait for page to load

        // Asserts if at the end the user is at the stuff index again
        $this->assertEquals(URL::action('AdminController@getIndex'),$this->browser->getLocation());

        $this->assertBodyHasText("#YOLO");
    }

    public function testPricing()
    {   
        $this->browser
            ->open(URL::action('AdminController@getIndex'))
            ->click(l::linkContaining('Pricing Plans'))   
            ->waitForPageToLoad(2000);                      // Wait for page to load

        $this->assertEquals(URL::action('AdminController@getPricingList'),$this->browser->getLocation());

        $this->browser
            ->click(l::linkContaining('View/Edit Plan Rules'))   
            ->waitForPageToLoad(2000);

        $this->assertBodyHasText("Plan Name:");

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

    public function testMerchants()
    {
        $this->browser
            ->open(URL::action('AdminController@getIndex'))
            ->click(l::linkContaining('Merchants'))   
            ->waitForPageToLoad(2000);                      // Wait for page to load

        // Asserts if at the end the user is at the stuff index again
        $this->assertEquals(URL::action('AdminController@getMerchants'),$this->browser->getLocation());

        $this->assertBodyHasText($this->merchant->id);
        $this->assertBodyHasText($this->merchant->email);
    }
    
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

    public function testMerchantDetails()
    {   
        $this->browser
            ->open(URL::action('AdminController@getMerchants'))
            ->waitForPageToLoad(2000)
            ->click(l::linkContaining('Merchant Details'))
            ->waitForPageToLoad(2000);

        $this->assertEquals(URL::to('/admin/merchant/'.$this->merchant->id.'/details'),$this->browser->getLocation());

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

    public function testMerchantActivation()
    {   
        $this->browser
            ->open(URL::to('admin/merchant/'.$this->merchant->id))
            ->waitForPageToLoad(2000)
            ->click(l::linkContaining('Activate Merchant'))
            ->waitForPageToLoad(2000);

        $this->browser   
            ->type(l::IdOrName('tid'), '12111')
            ->type(l::IdOrName('tid_password'), '123456')
            ->type(l::IdOrName('tid_password_confirmation'), '123456')
            ->select(l::IdOrName('pricing_plan'), 'index=1');

            // ->click(l::css('.btn-primary'))
            // ->waitForPageToLoad(2000);

        $this->assertBodyHasText('Merchant activated successfully');
    }

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