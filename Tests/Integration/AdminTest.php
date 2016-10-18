<?php

namespace Tests\Integration;

use Selenium\Locator as l;
use Laracasts\TestDummy\Factory;
use App\Admin;
use App\Merchant;
use App\MerchantDetails;
use App\User;
use URL;
use Uuid;
use Exception;
use Mockery;
use App;
use PHPUnit_Extensions_Selenium2TestCase_Keys as Keys;

class AdminTest extends TestCase
{
    protected $admin;

    protected $merchant;

    protected $merchant_details;

    protected static $migrated = false;

    public function setUp()
    {
        parent::setUp();

        Factory::$factoriesPath = __DIR__.'/../factories/';

        if (static::$migrated === false)
        {
            // Truncates all tables before first test
            $this->truncateAll();
            static::$migrated = true;
        }

        /**
         * Creates a new admin & merchant if none exist in db, else uses first admin.
         * This is necesssary to persist sessions between tests
         */
        try
        {
            $this->admin = Admin\Entity::firstorfail();
            $this->merchant_details = MerchantDetails\Entity::firstorfail();
            $this->merchant = $this->merchant_details->merchant();
            s($this->merchant->id);
        }
        catch(Exception $e)
        {
            $this->admin = $this->createEntity('admin');

            $user = $this->createEntity('user');
            $user->saveOrFail();

            $data = [
                'business_name' =>  substr(strtoupper(md5('Razorpay' . microtime())), 0, 20)
            ];

            $this->merchant = Merchant\Entity::createFromUser($user, $data);
            $this->merchant->saveOrFail();
            $this->merchant_details = $this->createEntity('merchant_details',[
                'merchant_id'   =>  $this->merchant->id,
                'business_name' =>  $data['business_name'],
                'bank_branch_ifsc' => 'KKBK0000261',
                'submitted'     => 1,
                'bank_account_number' => '432432422424'
            ]);

            $user->merchants()->attach($this->merchant, ['role' => 'owner']);
            try
            {
                $error = (new Merchant\Service)
                    ->confirmMerchantById($this->merchant->id);
            }

            catch(\Razorpay\Api\Errors\Error $e)
            {
                $error = [$e->getMessage()];
            }

            if (empty($error) === false)
            {
                $this->fail($error[0]);
            }
        }
    }

    public function setUpPage()
    {
        $this->timeouts()->implicitWait(10000);

        $this->url('admin#');
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->truncateAll();
        $this->closeWindow();
    }

    /**
     * Tests admin login
     */
    public function testLogin()
    {
        $this->waitUntilContainsByCss('h1', 'Pending Activations');
    }

    /**
     * Tests Pricing Module of admin
     */
    public function testPricing()
    {
        // Check opening of pricing page from dashboard
        $this->clickById('pricingNav');
        $this->waitUntilContainsByCss('body', 'List of all Plans');

        // Tests creation of new plan
        $this->waitAndClickByXPath('a','text','Create New Pricing Plan');
        $this->assertTrue($this->displayedByClassName('panel-heading'));
        $this->setValueByName('plan_name', static::generateRandomString(7));
        $this->selectByNameAndValue('payment_method', "card");
        $this->selectByNameAndValue('payment_method_type', "credit");
        $this->selectByNameAndValue('international', "0");
        $this->selectByNameAndValue('amount_range_active', "0");
        $this->setValueByName('percent_rate', '280');
        $this->setValueByName('fixed_rate', '200');
        $this->clickByXPath('button','text','Save and Add More Rules');
        $this->waitUntilContainsByClassName('alert-success', 'Plan created successfully');

        // Tests Creation of new rule
        $this->waitUntilDisplayedById('show-plan-panel');
        $this->selectByNameAndValue('payment_method', "card");
        $this->selectByNameAndValue('payment_method_type', 'debit');
        $this->selectByNameAndValue('international', '0');
        $this->selectByNameAndValue('amount_range_active', '0');
        $this->setValueByName('percent_rate', '200');
        $this->setValueByName('fixed_rate', '0');
        $this->clickByXPath('button','text','Save');
        $this->waitUntilContainsByClassName('alert-success', 'Plan created successfully');
    }

    /**
     * Tests merchant listing for admin
     */
    public function testMerchantsList()
    {
        $this->currentWindow()->size(array(
          'width' => 2560,
          'height' => 1600,
        ));
        $this->waitAndClickById('merchantsNav');
        $this->assertTrue($this->displayedByClassName('merchant_type'));
        $this->displayedByCss('div.butterbar.hide');
        $this->execScript('$("body").css("MozTransform", "scale(1,1)")');
        $this->execScript('$(".merchant_type").val("0").trigger("change")');
        $this->execScript('$(".merchant_go").click()');
        $this->waitUntilDisplayedByClassName('merchants-table-body');
        $this->waitUntilContainsByCss('body', $this->merchant->id);
        $this->waitUntilContainsByCss('body', $this->merchant->email);
    }

    /**
     * Tests merchant details management for admin
     */
    public function testMerchantDetails()
    {
        $this->currentWindow()->size(array(
          'width' => 2560,
          'height' => 1600,
        ));
        $this->waitAndClickById('merchantsNav');
        $this->assertTrue($this->displayedByClassName('merchant_type'));
        $this->execScript('$(".merchant_type").val("0").trigger("change")');
        $this->execScript('$(".merchant_go").click()');
        $this->assertTrue($this->displayedByClassName('merchants-table-body'));
        $this->clickByLinkText($this->merchant->id);
        $this->window($this->windowHandles()[1]);
        $this->waitUntilDisplayedByClassName('merchant-wrapper');
        $this->waitUntilContainsByCss('body', $this->merchant->id);
        $this->waitUntilContainsByCss('body', 'Merchant Detail');

        // Lock Activation Form
        $this->execScript('$(".lock-form").click()');
        $this->waitUntilDisplayedByClassName('alert-success');
        $this->waitUntilContainsByCss('body', 'Merchant Form locked successfully');

        // Unlock Activation Form
        $this->execScript('$(".unlock-form").click()');
        $this->waitUntilDisplayedByClassName('alert-success');
        $this->waitUntilContainsByCss('body', 'Merchant Form unlocked successfully');

        //Assign Pricing
        $this->execScript('$(".assign-pricing").click()');
        $this->waitUntilDisplayedByClassName('pricing-modal');
        $this->waitUntilDisplayedByName('pricing_plan_id');
        //$this->select($this->byXPath('//select[@Name="pricing_plan_id"]/option[0]'));
        $this->clickByClassName('modal-ok');
        $this->waitUntilDisplayedByClassName('confirm-modal');
        $this->clickByClassName('confirm-ok');
        $this->waitUntilAbsentByCss('.pricing-modal');
        $this->waitUntilAbsentByCss('.alert-danger');
        $this->waitUntilContainsByCss('body', 'Plan Assigned successfully');

        // Assign Terminal
        $terminalPassword = static::generateRandomInteger(8);
        $this->execScript('$(".assign-terminal").click()');
        $this->waitUntilDisplayedByClassName('terminal-modal');
        $this->setValueByName('gateway_merchant_id', static::generateRandomInteger(5));
        $this->setValueByName('gateway_terminal_id', static::generateRandomInteger(8));
        $this->setValueByName('gateway_terminal_password', $terminalPassword);
        $this->setValueByName('gateway_terminal_password_confirmation', $terminalPassword);
        $this->setValueByName('category', '3456');
        $this->clickByClassName('modal-ok');
        $this->waitUntilDisplayedByClassName('confirm-modal');
        $this->clickByClassName('confirm-ok');
        $this->waitUntilAbsentByCss('.terminal-modal');
        $this->waitUntilAbsentByCss('.alert-danger');
        $this->waitUntilContainsByCss('body', 'Terminal Assigned successfully');

        // Edit Merchant Details
        $this->execScript('$(".edit-merchant").click()');
        $this->waitUntilDisplayedByClassName('merchant-modal');
        $this->setValueByName('category', '1234');
        $this->setValueByName('website', 'http://razorpay.com');
        $this->setValueByName('billing_label', 'razorpay');
        $this->setValueByName('transaction_report_email', 'test@razorpay.com, nemo@razorpay.com');
        $this->setValueByName('settlement_schedule', '5');
        $this->clickByClassName('modal-ok');
        $this->waitUntilAbsentByCss('.alert-danger');
    }

    /**
     * Tests login as merchant for admin
     */
    public function testLoginAsMerchant()
    {
        $this->currentWindow()->size(array(
          'width' => 2560,
          'height' => 1600,
        ));
        $this->waitAndClickById('merchantsNav');
        $this->execScript('$(".merchant_type").val("0").trigger("change")');
        $this->execScript('$(".merchant_go").click()');
        $this->assertTrue($this->displayedByClassName('merchants-table-body'));
        $this->clickByXPath('a','text',$this->merchant->id);
        $this->window($this->windowHandles()[1]);
        $this->waitUntilDisplayedByClassName('merchant-wrapper');
        $this->waitUntilContainsByCss('body', $this->merchant->id);
        $this->waitUntilContainsByCss('body', 'Merchant Detail');
        $this->clickByLinkText('Login as Merchant');
        $this->window($this->windowHandles()[2]);
        $this->waitUntilContainsByCss('body', 'Welcome to Razorpay');

        // Logout is currently broken
        // TODO: Uncomment this
        // $this->browser
        //         ->open(URL::to('/user/logout'))
        //         ->waitForPageToLoad(20000);
    }

    /**
     * Tests checking of merchant activation details by admin
     */
    public function testMerchantActivationDetails()
    {
        $this->currentWindow()->size(array(
          'width' => 2560,
          'height' => 1600,
        ));
        // Browsing the whole form
        $this->waitAndClickById('merchantsNav');
        $this->execScript('$(".merchant_type").val("0").trigger("change")');
        $this->execScript('$(".merchant_go").click()');
        $this->assertTrue($this->displayedByClassName('merchants-table-body'));
        $this->clickByXPath('a','text',$this->merchant->id);
        $this->window($this->windowHandles()[1]);
        $this->waitUntilDisplayedByClassName('merchant-wrapper');
        $this->waitUntilContainsByCss('body', $this->merchant->id);
        $this->waitUntilContainsByCss('body', 'Merchant Detail');
        $this->execScript('$(".see-activation-form").click()');
        $this->waitUntilDisplayedByClassName('activation-wrapper');
        $this->assertTrue($this->displayedByCss('form[name=step1]'));
        $this->clickByLinkText('Business Details');
        $this->assertTrue(!$this->displayedByCss('form[name=step1]'));
        $this->assertTrue($this->displayedByCss('form[name=step2]'));
        $this->clickByLinkText('Website Details');
        $this->assertTrue($this->displayedByCss('form[name=step3]'));
        $this->clickByLinkText('Bank Account Details');
        $this->assertTrue($this->displayedByCss('form[name=step4]'));
        $this->clickByLinkText('Documents Upload');
        $this->assertTrue($this->displayedByCss('form[name=step5]'));
        $this->clickByLinkText('Submit Form');
        $this->assertTrue($this->displayedByCss('form[name=step6]'));
    }

    public function testMerchantTagging()
    {
        $this->currentWindow()->size(array(
          'width' => 2560,
          'height' => 1600,
        ));
        $this->waitAndClickById('merchantsNav');
        $this->execScript('$(".merchant_type").val("0").trigger("change")');
        $this->execScript('$(".merchant_go").click()');
        $this->assertTrue($this->displayedByClassName('merchants-table-body'));
        $this->clickByXPath('a','text',$this->merchant->id);
        $this->window($this->windowHandles()[1]);
        $this->waitUntilDisplayedByClassName('merchant-wrapper');
        $this->waitUntilContainsByCss('body', $this->merchant->id);
        $this->waitUntilContainsByCss('body', 'Merchant Detail');
        $this->keys(Keys::PAGEDOWN);
        $this->execScript('$(".tag-merchant").click()');
        $this->setValueByName('merchant-tags', 'international,webhook,random_tag');
        $this->execScript('$(".modal-ok").click()');
        $this->waitUntilContainsByCss('body', 'Random_Tag');
    }

    /**
     * Tests Admins Mangement
     */
    public function testManageAdmins()
    {
        $this->currentWindow()->size(array(
          'width' => 2560,
          'height' => 1600,
        ));
        // Testing admins display
        $this->waitUntilDisplayedById('adminsNav');
        $this->clickById('adminsNav');
        $this->waitUntilDisplayedByClassName('admins-table');
        $this->waitUntilContainsByCss('body', $this->admin->name);
        $this->waitUntilContainsByCss('body', $this->admin->username);

        // Test Add Admin
        $this->clickByLinkText('Add new Admin');
        $this->waitUntilDisplayedByClassName('new-admin-modal');
        $this->setValueByName('name', 'Tester');
        $this->setValueByName('username', static::generateRandomString(7));
        $this->setValueByName('email', static::generateMerchantEmail());
        $this->setValueByName('password', '1234567');
        $this->setValueByName('password_confirmation', '1234567');
        $this->clickByXPath('button','text','OK');
        $this->waitUntilAbsentByCss('.new-admin-modal');
        $this->waitUntilContainsByCss('body', 'Admin created successfully');

        // Test Promote Admin
        $this->execScript('$("a[class=\"btn-admin-promote\"]").click()');
        //$this->clickByClassName('btn-admin-promote');
        $this->waitUntilDisplayedByClassName('confirm-ok');
        $this->clickByClassName('confirm-ok');
        $this->waitUntilAbsentByCss('.confirm-modal');
        $this->waitUntilContainsByCss('body', 'Admin promoted successfully');

        $this->execScript('$("a[class=\"btn-admin-delete\"]").click()');
        $this->waitUntilDisplayedByClassName('confirm-ok');
        $this->clickByClassName('confirm-ok');
        $this->waitUntilAbsentByCss('.confirm-modal');
        $this->waitUntilContainsByCss('body', 'Admin deleted successfully');
    }

    /**
     * Tests admin logout
     */
    public function testLogout()
    {
        $this->currentWindow()->size(array(
          'width' => 2560,
          'height' => 1600,
        ));
        $this->waitUntilDisplayedByClassName('user-dropdown');
        $this->clickByClassName('user-dropdown');
        $this->clickByLinkText('Logout');
        $this->waitUntilDisplayedByCss('body', 'You are successfully logged out');
    }
}
