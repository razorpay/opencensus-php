<?php

use Selenium\Locator as l;

class AdminTest extends IntegrationTestCase
{   
    protected $admin;

    public function setUp()
    {   
        parent::setUp();

        try
        {
            $this->admin = Models\DAL\Admin::firstorfail();
        }
        catch(Exception $e)
        {
            $this->admin = $this->createEntity('admin');
        }
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