<?php

use Selenium\Locator as l;

class MerchantTest extends IntegrationTestCase
{   
    protected $merchant;

    public function setUp()
    {   
        parent::setUp();

        try
        {
            $this->merchant = Models\DAL\Merchant::firstorfail();
        }
        catch(Exception $e)
        {
            $this->merchant = $this->buildEntity('merchant', array('email' =>static::generateMerchantEmail()));
        }
    }

    public function testRegister()
    {
        $this->browser
            ->open(URL::action('MerchantController@getRegister'))    // Visits the 'register page
            ->type(l::IdOrName('name'), $this->merchant->name)      // Fill name
            ->type(l::IdOrName('email'), $this->merchant->email)   // Fill email
            ->type(l::IdOrName('password'), '123456') 
            ->type(l::IdOrName('password_confirmation'), '123456')   
            ->click(l::css('#form-button'))                 // Click in the button
            ->waitForPageToLoad(2000);                      // Wait for page to load

        // Asserts if at the end the user is at the stuff index again
        $this->assertEquals(URL::action('MerchantController@postRegister'),$this->browser->getLocation());

        $this->assertBodyHasText("Please check your inbox for activation email from Razorpay.");
    }


    public function testUserConfirmation()
    {   
        $confirm_token = $this->merchant->confirm_token;

        $this->browser
            ->open(URL::to('/register/confirm/'.$confirm_token))    // Visits the 'register page
            ->waitForPageToLoad(1000);

        $this->assertBodyHasText("Please save your Razorpay API credentials carefully.");
    }

    public function testLogout()
    {
        $this->browser
            ->open(URL::action('MerchantController@getIndex'))    // Visits the 'stuff' index
            ->click(l::linkContaining('Sign Out'))                 // Click in the button
            ->waitForPageToLoad(2000);                      // Wait for page to load
        
        // Asserts if at the end the user is at the stuff index again
        $this->assertEquals(URL::action('MerchantController@getLogin'),$this->browser->getLocation());
    }

    public function testLogin()
    {
        $this->browser
            ->open(URL::action('MerchantController@getLogin'))    // Visits the 'stuff' index
            ->type(l::IdOrName('email'), $this->merchant->email)   // Fill name
            ->type(l::IdOrName('password'), '123456')   // Fill slug
            ->click(l::css('#form-button'))                 // Click in the button
            ->waitForPageToLoad(2000);                      // Wait for page to load

        // Asserts if at the end the user is at the stuff index again
        $this->assertEquals(URL::action('MerchantController@getIndex').'/#!/',$this->browser->getLocation());

        $this->assertBodyHasText("Last Transfer");

        $this->assertBodyHasText("Overview");

        $this->assertBodyHasText("Successful Transactions");
    }

    public function testTransactionsPanel()
    {
         $this->browser
            ->open(URL::action('MerchantController@getIndex'))
            ->click(l::linkContaining('Transactions'))   
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#transaction-count-line-chart-full > div').length > 0", 2000);;  

        $this->assertEquals(URL::action('MerchantController@getIndex').'/#!/transactions',$this->browser->getLocation());

        $this->assertBodyHasText("Successful Transactions");

        $this->assertBodyHasText("Recent Transactions");

    }

    public function testRefundsPanel()
    {
         $this->browser
            ->open(URL::action('MerchantController@getIndex'))
            ->click(l::linkContaining('Refunds'))   
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#refund-list > div').length > 0", 2000);;  

        $this->assertEquals(URL::action('MerchantController@getIndex').'/#!/refunds',$this->browser->getLocation());

        $this->assertBodyHasText("Recent Refunds");
    }

    public function testSettlementsPanel()
    {
         $this->browser
            ->open(URL::action('MerchantController@getIndex'))
            ->click(l::linkContaining('Settlements'))   
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#settle-list > div').length > 0", 2000);;  

        $this->assertEquals(URL::action('MerchantController@getIndex').'/#!/settlements',$this->browser->getLocation());

        $this->assertBodyHasText("Recent Settlements");
    }

    public function testKeysPanel()
    {
         $this->browser
            ->open(URL::action('MerchantController@getIndex'))
            ->click(l::linkContaining('API Keys'))   
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#keys > div').length > 0", 2000);  

        $this->assertEquals(URL::action('MerchantController@getIndex').'/#!/keys',$this->browser->getLocation());

        $this->assertBodyHasText("Key ID");


        $this->browser
            ->click(l::css('.roll-href'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.roll-key-form-wrapper').hasClass('hidden') == false", 2000)
            ->click(l::css('.roll-key-button'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.roll-key-form-wrapper .result').html().length > 0", 2000);



        $this->assertBodyHasText("Click here to download credentials. You will not be able to view the credentials again.");
    }

    public function testAccountPanel()
    {
         $this->browser
            ->open(URL::action('MerchantController@getIndex'))
            ->click(l::linkContaining('Account'))   
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#account-name').html().length > 0", 2000);

        $this->assertEquals(URL::action('MerchantController@getIndex').'/#!/account',$this->browser->getLocation());

        $this->assertBodyHasText($this->merchant->name);
    }



    

    protected static function generateMerchantEmail()
    {
        return static::generateRandomString()."@".static::generateRandomString().".com";
    }
}