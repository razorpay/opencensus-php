<?php
namespace Tests\Integration;

use Selenium\Locator as l;
use Models;
use URL;
use Exception;

class MerchantTest extends TestCase
{   
    protected $merchant;

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
            ->open(URL::to('/#/access/signup'))    // Visits the 'register page
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"signup\"]').length > 0", 20000)
            ->type(l::IdOrName('name'), $this->merchant->name)      // Fill name
            ->type(l::IdOrName('email'), $this->merchant->email)   // Fill email
            ->type(l::IdOrName('password'), '123456') 
            ->type(l::IdOrName('password_confirmation'), '123456')   
            ->click(l::IdOrName('agree'))
            ->click(l::IdOrName('submit'))                 // Click in the button
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.alert-success').length > 0", 20000);  

        $this->assertBodyHasText("Please check your inbox for confirmation email from Razorpay");
    }

    /**
     * Tests merchant confirmation
     */
    public function testUserConfirmation()
    {   
        $confirm_token = $this->merchant->confirm_token;

        $this->browser
            ->open(URL::to('/admin'))
            ->open(URL::to('/#/access/confirm/'.$confirm_token))    // Visits the 'register page
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.alert-success').length > 0", 20000);

        $this->assertBodyHasText("Confirmation successfull.");
    }

    /**
     * Tests merchant login
     */
    public function testLogin()
    {
        $this->browser
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"signin\"]').length > 0", 20000)
            ->type(l::IdOrName('email'), $this->merchant->email)   // Fill name
            ->type(l::IdOrName('password'), '123456')   // Fill slug
            ->click(l::IdOrName('submit'))                 // Click in the button
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.navbar').length > 0", 20000);                     // Wait for page to load

        $this->assertBodyHasText("Welcome to Razorpay");

        $this->assertBodyHasText("Total Transactions");

        $this->assertBodyHasText("Successful Transactions");
    }

    /**
     * Tests transactions panel display
     */
    public function testTransactionsList()
    {
        $this->browser
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#transactionsNav').length > 0", 20000)
            ->click(l::IdOrName('transactionsNav')) 
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.transactions-table').length > 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.alert').length == 0", 20000);    

        $this->assertBodyHasText("List of all transactions");
    }

    /**
     * Tests refunds panel display
     */
    public function testRefundsPanel()
    {
        $this->browser
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#refundsNav').length > 0", 20000)
            ->click(l::IdOrName('refundsNav')) 
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.refunds-table').length > 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.alert').length == 0", 20000);    

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
        //Testing keys display
         $this->browser
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#keysNav').length > 0", 20000)
            ->click(l::IdOrName('keysNav')) 
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.keys-table').length > 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.alert').length == 0", 20000);    

        $this->assertBodyHasText("API Keys");

        $this->assertBodyHasText("Key Id");

        //Testing Generate new Key
        $this->browser
            ->click(l::IdOrName('generateKey'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.new-key-modal').length > 0", 20000);

        $this->assertBodyHasText("New Key");

        $this->assertBodyHasText("Key Generated");  

        //New Key generated close the modal
        $this->browser
            ->click(l::css('.modal-ok'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.confirm-modal').length > 0", 20000)
            ->click(l::css('.confirm-ok'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.new-key-modal').length == 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.alert-success').length > 0", 20000);  
        
        //Testing rolling of key
        $this->browser
            ->click(l::css('.roll_key'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.roll-key-modal').length > 0", 20000)    
            ->click(l::css('.modal-ok'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.new-key-modal').length > 0", 20000)
            ->click(l::css('.modal-ok'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.confirm-ok').length > 0", 20000)
            ->click(l::css('.confirm-ok'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.new-key-modal').length == 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.alert-success').length > 0", 20000);

    }

    /**
     * Tests activation panel display and form filling
     */
    public function testActivationPanel()
    {
        ///Testing keys display
         $this->browser
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#activationNav').length > 0", 20000)
            ->click(l::IdOrName('activationNav')) 
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.activation-wrapper').length > 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.alert').length == 0", 20000);   

        $this->assertBodyHasText("Contact Details");

        //Fill in Contact details and save
        $this->browser
            ->click(l::css('form[name="step1"] > fieldset > .prev-next > .btn-save'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step1\"] > fieldset > .alerts > .alert-danger').length > 0", 20000)
            ->type(l::IdOrName('contact_name'), 'Yo Name')   // Fill name
            ->type(l::IdOrName('contact_email'), 'email@umail.com')   // Fill slug
            ->type(l::IdOrName('contact_mobile'), '9199192999') 
            ->type(l::IdOrName('contact_landline'), '9191929393') 
            ->click(l::css('form[name="step1"] > fieldset > .prev-next > .btn-save'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step1\"] > fieldset > .alerts > .alert-success').length > 0", 20000);

        $this->assertFalse($this->browser->isElementPresent(l::css('form[name=\"step1\"] > fieldset > .alerts > .alert-danger')));

        //Fill in Bussiness Details and save
        $this->browser
            ->click(l::css('form[name="step1"] > fieldset > .prev-next > .btn-next'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step2\"]').is(':visible')", 20000)
            ->select(l::IdOrName('bussiness_type'), 'Partnership')
            ->type(l::IdOrName('bussiness_category'), 'Category')   // Fill name
            ->type(l::IdOrName('bussiness_subcategory'), 'SubCategory')   // Fill slug
            ->type(l::IdOrName('bussiness_registered_address'), 'address 1, 2') 
            ->type(l::IdOrName('bussiness_registered_state'), 'state') 
            ->type(l::IdOrName('bussiness_registered_city'), 'city') 
            ->type(l::IdOrName('bussiness_registered_pin'), '333333') 
            ->click(l::IdOrName('or_same'))
            ->type(l::IdOrName('bussiness_doe'), '02/02/1992')
            ->type(l::IdOrName('company_cin'), 'cin123455')
            ->type(l::IdOrName('company_pan'), 'pan12345')
            ->type(l::IdOrName('company_pan_name'), 'pan name')
            ->type(l::IdOrName('bussiness_model'), 'my model')
            ->select(l::IdOrName('transaction_volume'), '1 to 10 lakh')
            ->type(l::IdOrName('transaction_value'), '120')
            ->click(l::css('form[name="step2"] > fieldset > .prev-next > .btn-save'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step2\"] > fieldset > .alerts > .alert-success').length > 0", 20000);

        $this->assertFalse($this->browser->isElementPresent(l::css('form[name=\"step2\"] > fieldset > .alerts > .alert-danger')));

        //Fill in Promoters Details and save
        $this->browser
            ->click(l::css('form[name="step2"] > fieldset > .prev-next > .btn-next'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step3\"]').is(':visible')", 20000)
            ->type(l::IdOrName('promoter_pan'), 'PAE123')   // Fill name
            ->type(l::IdOrName('promoter_pan_name'), 'Promoter')   // Fill slug
            ->click(l::css('form[name="step3"] > fieldset > .prev-next > .btn-save'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step3\"] > fieldset > .alerts > .alert-success').length > 0", 20000);

        $this->assertFalse($this->browser->isElementPresent(l::css('form[name=\"step3\"] > fieldset > .alerts > .alert-danger')));

        //Fill in Bank Account Details and save
        $this->browser
            ->click(l::css('form[name="step3"] > fieldset > .prev-next > .btn-next'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step4\"]').is(':visible')", 20000)
            ->type(l::IdOrName('bank_name'), 'BankName')   // Fill name
            ->type(l::IdOrName('bank_account_number'), '123443')   // Fill slug
            ->type(l::IdOrName('bank_account_name'), 'Tester')   // Fill slug
            ->type(l::IdOrName('bank_account_type'), 'savings')   // Fill slug
            ->type(l::IdOrName('bank_branch_address'), 'Abcd')   // Fill slug
            ->type(l::IdOrName('bank_branch_ifsc'), 'abc123443')   // Fill slug
            ->click(l::css('form[name="step4"] > fieldset > .prev-next > .btn-save'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step4\"] > fieldset > .alerts > .alert-success').length > 0", 20000);

        $this->assertFalse($this->browser->isElementPresent(l::css('form[name=\"step4\"] > fieldset > .alerts > .alert-danger')));

        //Upload documents and save
        //S3 API is mocked in selenium/init.php to avoid requests to AWS
        $this->browser
            ->click(l::css('form[name="step4"] > fieldset > .prev-next > .btn-next'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step5\"]').is(':visible')", 20000)
            ->attachFile(l::IdOrName('bussiness_proof'), URL::to('/img/logo.png'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step5\"] > fieldset > div > div > .alerts > .alert-success').length == 1", 20000)
            ->attachFile(l::IdOrName('bussiness_pan_proof'), URL::to('/img/logo.png'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step5\"] > fieldset > div > div > .alerts > .alert-success').length == 2", 20000)
            ->attachFile(l::IdOrName('promoter_pan_proof'), URL::to('/img/logo.png'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step5\"] > fieldset > div > div > .alerts > .alert-success').length == 3", 20000)
            ->attachFile(l::IdOrName('address_proof'), URL::to('/img/logo.png'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step5\"] > fieldset > div > div > .alerts > .alert-success').length == 4", 20000)
            ->click(l::css('form[name="step5"] > fieldset > .prev-next > .btn-save'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step5\"] > fieldset > .alerts > .alert-success').length > 0", 20000);

        $this->assertFalse($this->browser->isElementPresent(l::css('form[name=\"step4\"] > fieldset > .alerts > .alert-danger')));

        //Submit for activation
        $this->browser
            ->click(l::css('form[name="step5"] > fieldset > .prev-next > .btn-next'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step6\"]').is(':visible')", 20000)
            ->click(l::IdOrName('agree_terms'))
            ->click(l::css('.btn-submit'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"step6\"] > fieldset > .alerts > .alert-success').length > 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.activation-wrapper > .alerts > .alert-info').length > 0", 20000);
        
        $this->assertFalse($this->browser->isElementPresent(l::css('form[name=\"step6\"] > fieldset > .alerts > .alert-danger')));
    }

    /**
     * Tests Profile Panel Display
     */
    public function testProfilePanel()
    {
        //Testing profile display
        $this->browser
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('#profileNav').length > 0", 20000)
            ->click(l::IdOrName('profileNav')) 
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.profile-wrapper').length > 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.alert').length == 0", 20000);    

        $this->assertBodyHasText($this->merchant->name);

        $this->assertBodyHasText($this->merchant->id);

        //Test Change Password
        $this->browser
            ->click(l::css('.btn-change-pwd'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.change-pwd-modal').length > 0", 20000)
            ->type(l::IdOrName('old_password'), '123456')
            ->type(l::IdOrName('password'), '1234567')
            ->type(l::IdOrName('password_confirmation'), '1234567')
            ->click(l::css('.modal-ok'))                 // Click in the button
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.change-pwd-modal').length == 0", 20000)
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.alert-success').length > 0", 20000);

        $this->assertBodyHasText("Password changed successfully");
    }

    /**
     * Tests merchant logout since he is logged in after confirmation
     */
    public function testLogout()
    {
        $this->browser
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('.user-dropdown').length > 0", 20000)
            ->click(l::css('.user-dropdown'))                 
            ->click(l::linkContaining('Logout'))
            ->waitForCondition("selenium.browserbot.getCurrentWindow().$('form[name=\"signin\"]').length > 0", 20000);
    }
}