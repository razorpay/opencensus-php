<?php
namespace Tests\Integration;

use Selenium\Locator as l;
use Selenium\Browser;
use URL;

class BrowserWrapper extends Browser
{
    // CSS selector ID
    public function waitAndClickById($id)
    {
        return $this->waitForCondition("selenium.browserbot.getCurrentWindow().$('#$id').length > 0", 20000)
            ->click(l::IdOrName($id));
    }

    public function waitForPresent($selector)
    {
        return $this->waitForCondition("selenium.browserbot.getCurrentWindow().$('$selector').length > 0", 20000);
    }

    public function waitForVisible($selector)
    {
        return $this->waitForCondition("selenium.browserbot.getCurrentWindow().$('$selector').is(':visible')", 20000);
    }

    public function waitForAbsent($selector)
    {
        return $this->waitForCondition("selenium.browserbot.getCurrentWindow().$('$selector').length == 0", 20000);
    }

    public function waitForLoaded()
    {
        return $this->waitForCondition("selenium.browserbot.getCurrentWindow().$('.butterbar.hide').length == 2", 20000);
    }

    public function isError()
    {
        return $this->isElementPresent(l::css('.alert-danger'));
    }

    public function clickLinkWithText($text)
    {
        return $this->click(l::linkContaining($text));
    }

    public function open($url)
    {
        $url = URL::to($url);
        return parent::open($url);
    }
}
