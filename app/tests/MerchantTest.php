<?php

use Selenium\Locator as l;

class MerchantTest extends IntegrationTestCase
{   
    protected $merchant;

    public function setUp()
    {   
        parent::setUp();

        //Creates a new merchant if none exists in db otherwise uses that. This is necessary for persisting sessions between tests
        try
        {
            $this->merchant = Models\DAL\Merchant::firstorfail();
        }
        catch(Exception $e)
        {
            $this->merchant = $this->buildEntity('merchant', array('email' =>static::generateMerchantEmail()));
        }
    }

    /**
     * Tests merchant registration
     */
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

        $this->assertEquals(URL::action('MerchantController@postRegister'),$this->browser->getLocation());

        $this->assertBodyHasText("Please check your inbox for activation email from Razorpay.");
    }

    /**
     * Tests merchant confirmation
     */
    public function testUserConfirmation()
    {   
        $confirm_token = $this->merchant->confirm_token;

        $this->browser
            ->open(URL::to('/register/confirm/'.$confirm_token))    // Visits the 'register page
            ->waitForPageToLoad(1000);

        $this->assertBodyHasText("Please save your Razorpay API credentials carefully.");
    }

    /**
     * Tests merchant logout since he is logged in after confirmation
     */
    public function testLogout()
    {
        $this->browser
            ->open(URL::action('MerchantController@getIndex'))    // Visits the 'stuff' index
            ->click(l::linkContaining('Sign Out'))                 // Click in the button
            ->waitForPageToLoad(2000);                      // Wait for page to load
        
        // Asserts if at the end the user is at the stuff index again
        $this->assertEquals(URL::action('MerchantController@getLogin'),$this->browser->getLocation());
    }

    /**
     * Tests merchnat login
     */
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

    /**
     * Tests transactions panel display
     */
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

    /**
     * Tests refunds panel display
     */
    public function testRefundsPanel()
    {
         $this->browser
            ->open(URL::action('MerchantController@getIndex'))
            ->click(l::linkContaining('Refunds'))   
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#refund-list > div').length > 0", 2000);;  

        $this->assertEquals(URL::action('MerchantController@getIndex').'/#!/refunds',$this->browser->getLocation());

        $this->assertBodyHasText("Recent Refunds");
    }

    /**
     * Tests settlements panel display
     */
    public function testSettlementsPanel()
    {
         $this->browser
            ->open(URL::action('MerchantController@getIndex'))
            ->click(l::linkContaining('Settlements'))   
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#settle-list > div').length > 0", 2000);;  

        $this->assertEquals(URL::action('MerchantController@getIndex').'/#!/settlements',$this->browser->getLocation());

        $this->assertBodyHasText("Recent Settlements");
    }

    /**
     * Tests keys panel display and rolling of keys
     */
    public function testKeysPanel()
    {
         $this->browser
            ->open(URL::action('MerchantController@getIndex'))
            ->click(l::linkContaining('API Keys'))   
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#keys > div').length > 0", 2000);  

        $this->assertEquals(URL::action('MerchantController@getIndex').'/#!/keys',$this->browser->getLocation());

        $this->assertBodyHasText("Key ID");

        //Testing rolling of key
        $this->browser
            ->click(l::css('.roll-href'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.roll-key-form-wrapper').hasClass('hidden') == false", 2000)
            ->click(l::css('.roll-key-button'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.roll-key-form-wrapper .result').html().length > 0", 2000);



        $this->assertBodyHasText("Click here to download credentials. You will not be able to view the credentials again.");
    }

    /**
     * Tests activation panel display and form filling
     */
    public function testActivationPanel()
    {
        //DIsplay activation form
         $this->browser
            ->open(URL::action('MerchantController@getIndex'))
            ->click(l::linkContaining('Activation'))   
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activation-form > fieldset:eq(0)').is(':visible')", 2000);  

        $this->assertEquals(URL::action('MerchantController@getIndex').'/#!/activation',$this->browser->getLocation());

        $this->assertBodyHasText("Contact Details");

        //Fill in Contact details and save
        $this->browser
            ->click(l::css('#activation-form > fieldset:eq(0) > .prev-next > .save'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activation-form > fieldset:eq(0) > .alert-danger').length > 0", 2000)
            ->type(l::IdOrName('contact_name'), 'Yo Name')   // Fill name
            ->type(l::IdOrName('contact_email'), 'email@umail.com')   // Fill slug
            ->type(l::IdOrName('contact_mobile'), '9199192999') 
            ->type(l::IdOrName('contact_landline'), '9191929393') 
            ->click(l::css('#activation-form > fieldset:eq(0) > .prev-next > .save'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activation-form > fieldset:eq(0) > .alert-success').length > 0", 2000);

        $this->assertFalse($this->browser->isElementPresent(l::css('#activation-form > fieldset:eq(0) > .alert-danger')));

        //Fill in Bussiness Details and save
        $this->browser
            ->click(l::css('#activation-form > fieldset:eq(0) > .prev-next > .next'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activation-form > fieldset:eq(1)').is(':visible')", 2000)
            ->select(l::IdOrName('bussiness_type'), 'Partnership')
            ->type(l::IdOrName('bussiness_category'), 'Category')   // Fill name
            ->type(l::IdOrName('bussiness_subcategory'), 'SubCategory')   // Fill slug
            ->type(l::IdOrName('bussiness_registered_address'), 'address 1, 2') 
            ->type(l::IdOrName('bussiness_registered_state'), 'state') 
            ->type(l::IdOrName('bussiness_registered_city'), 'city') 
            ->type(l::IdOrName('bussiness_registered_pin'), '333333') 
            ->click(l::css('#bussiness-operation-checkbox'))
            ->type(l::IdOrName('bussiness_doe'), '02/02/1992')
            ->type(l::IdOrName('company_cin'), 'cin123455')
            ->type(l::IdOrName('company_pan'), 'pan12345')
            ->type(l::IdOrName('company_pan_name'), 'pan name')
            ->type(l::IdOrName('bussiness_model'), 'my model')
            ->select(l::IdOrName('transaction_volume'), '1 to 10 lakh')
            ->type(l::IdOrName('transaction_value'), '120')
            ->click(l::css('#activation-form > fieldset:eq(1) > .prev-next > .save'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activation-form > fieldset:eq(1) > .alert-success').length > 0", 2000);

        $this->assertFalse($this->browser->isElementPresent(l::css('#activation-form > fieldset:eq(1) > .alert-danger')));

        //Fill in Promoters Details and save
        $this->browser
            ->click(l::css('#activation-form > fieldset:eq(1) > .prev-next > .next'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activation-form > fieldset:eq(2)').is(':visible')", 2000)
            ->type(l::IdOrName('promoter_pan'), 'PAE123')   // Fill name
            ->type(l::IdOrName('promoter_pan_name'), 'Promoter')   // Fill slug
            ->click(l::css('#activation-form > fieldset:eq(2) > .prev-next > .save'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activation-form > fieldset:eq(2) > .alert-success').length > 0", 2000);

        $this->assertFalse($this->browser->isElementPresent(l::css('#activation-form > fieldset:eq(2) > .alert-danger')));

        //Fill in Bank Account Details and save
        $this->browser
            ->click(l::css('#activation-form > fieldset:eq(2) > .prev-next > .next'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activation-form > fieldset:eq(3)').is(':visible')", 2000)
            ->type(l::IdOrName('bank_name'), 'BankName')   // Fill name
            ->type(l::IdOrName('bank_account_number'), '123443')   // Fill slug
            ->type(l::IdOrName('bank_account_name'), 'Tester')   // Fill slug
            ->type(l::IdOrName('bank_account_type'), 'savings')   // Fill slug
            ->type(l::IdOrName('bank_branch'), 'Abcd')   // Fill slug
            ->type(l::IdOrName('bank_branch_ifsc'), 'abc123443')   // Fill slug
            ->click(l::css('#activation-form > fieldset:eq(3) > .prev-next > .save'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activation-form > fieldset:eq(3) > .alert-success').length > 0", 2000);

        $this->assertFalse($this->browser->isElementPresent(l::css('#activation-form > fieldset:eq(3) > .alert-danger')));

        //Upload documents and save
        //S3 API is mocked in selenium/init.php to avoid requests to AWS
        $this->browser
            ->click(l::css('#activation-form > fieldset:eq(3) > .prev-next > .next'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activation-form > fieldset:eq(4)').is(':visible')", 2000)
            ->attachFile(l::IdOrName('bussiness_proof'), URL::to('/img/logo.png'))
            ->attachFile(l::IdOrName('bussiness_pan_proof'), URL::to('/img/logo.png'))
            ->attachFile(l::IdOrName('promoter_pan_proof'), URL::to('/img/logo.png'))
            ->attachFile(l::IdOrName('address_proof'), URL::to('/img/logo.png'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activation-form > fieldset:eq(4) > div > div > .alert-success').length == 4", 2000)
            ->click(l::css('#activation-form > fieldset:eq(4) > .prev-next > .save'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activation-form > fieldset:eq(4) > .alert-success').length > 0", 2000);

        $this->assertFalse($this->browser->isElementPresent(l::css('#activation-form > fieldset:eq(4) > .alert-danger')));

        //Submit for activation
        $this->browser
            ->click(l::css('#activation-form > fieldset:eq(4) > .prev-next > .next'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activation-form > fieldset:eq(5)').is(':visible')", 2000)
            ->check(l::IdOrName('agree-terms'))
            ->click(l::css('#activateButton'))
            ->waitForPageToLoad(2000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activation-form > .alert-info').length > 0", 2000);

        $this->assertFalse($this->browser->isElementPresent(l::css('#activation-form > fieldset > .alert-danger')));
    }

    /**
     * Tests Account Panel Display
     */
    public function testAccountPanel()
    {
         $this->browser
            ->open(URL::action('MerchantController@getIndex'))
            ->click(l::linkContaining('Account'))   
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#account-name').html().length > 0", 2000);

        $this->assertEquals(URL::action('MerchantController@getIndex').'/#!/account',$this->browser->getLocation());

        $this->assertBodyHasText($this->merchant->name);
    }
}