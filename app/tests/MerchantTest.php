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
            ->waitForPageToLoad(1000);                      // Wait for page to load
        
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
            ->waitForPageToLoad(1000);                      // Wait for page to load

        // Asserts if at the end the user is at the stuff index again
        $this->assertEquals(URL::action('MerchantController@getIndex').'/#!/',$this->browser->getLocation());
    }


    public function testIndex()
    {
         $this->browser
            ->open(URL::action('MerchantController@getIndex'));

        // Asserts if at the end the user is at the stuff index again
        $this->assertEquals(URL::action('MerchantController@getIndex').'/#!/',$this->browser->getLocation());

        $this->assertBodyHasText("Last Transfer");

        $this->assertBodyHasText("Overview");

        $this->assertBodyHasText("Successful Transactions");
    }

    

    protected static function generateMerchantEmail()
    {
        return static::generateRandomString()."@".static::generateRandomString().".com";
    }
}