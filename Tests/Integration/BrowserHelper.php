<?php

namespace Tests\Integration;

trait BrowserHelper
{
	public function setValueByName($name, $value)
	{
		$this->byName($name)->clear();
		$this->byName($name)->value($value);
	}

	public function clickById($id)
	{
		$this->byId($id)->click();
	}

	public function clickByClassName($class)
	{
		$this->byClassName($class)->click();
	}

	public function clickByName($name)
	{
		$this->byName($name)->click();
	}

	public function submitByName($name)
	{
		$this->byName($name)->submit();
	}

	public function clickByLinkText($text)
	{
		$this->byLinkText($text)->click();
	}

	public function setValueById($id, $value)
	{
		$this->byId($id)->clear();
		$this->byId($id)->value($value);
	}

	public function displayedById($id)
	{
		return $this->byId($id)->displayed();
	}

	public function displayedByClassName($class)
	{
		return $this->byClassName($class)->displayed();
	}

	public function displayedByName($name)
	{
		return $this->byName($name)->displayed();
	}

	public function findByXPath($type, $attribute, $value)
	{
		$searchString = '//'.$type.'[';

		switch ($attribute) {
			case 'name':
				$searchString .= '@Name';
				break;

			case 'id':
				$searchString .= '@Id';
				break;

			case 'text':
				$searchString .= '.';
				break;

			default:
				break;
		}

		$searchString .= '="'.$value.'"]';

		return $this->byXPath($searchString);
	}

	public function clickByXPath($type, $attribute, $value)
	{
		$element = $this->findByXPath($type, $attribute, $value);

		$element->click();
	}

	public function displayedByXPath($type, $attribute, $value)
	{
		$element = $this->findByXPath($type, $attribute, $value);

		return $element->displayed();
	}

	public function findByCss($selector)
	{
		return $this->byCssSelector($selector);
	}

	public function findByClassName($class)
	{
		return $this->byClassName($class);
	}

	public function displayedByCss($selector)
	{
		return $this->byCssSelector($selector)->displayed();
	}

	public function waitUntilDisplayedByXPath($type, $attribute, $value)
	{
		$this->waitUntil(function() use ($type, $attribute, $value) {
            $this->assertTrue($this->displayedByXPath($type, $attribute, $value));
            return true;
        }, 20000);
	}

	public function waitAndClickByXPath($type, $attribute, $value)
	{
		$this->waitUntil(function() use ($type, $attribute, $value) {
            $this->clickByXPath($type, $attribute, $value);
            return true;
        }, 20000);
	}

	public function waitAndClickByClassName($class)
	{
		$this->waitUntil(function() use ($class) {
			$this->assertTrue($this->displayedByClassName($class));
            $this->clickByClassName($class);
            return true;
        }, 20000);
	}

	public function waitAndClickById($id)
	{
		$this->waitUntil(function() use ($id) {
			$this->assertTrue($this->displayedById($id));
            $this->clickById($id);
            return true;
        }, 20000);
	}

	public function waitAndClickByLinkText($text)
	{
		$this->waitUntil(function() use ($text) {
            $this->clickByLinkText($text);
            return true;
        }, 20000);
	}

	public function waitUntilDisplayedByCss($selector)
	{
		$this->waitUntil(function() use ($selector) {
            $this->assertTrue($this->displayedByCss($selector));
            return true;
        }, 20000);
	}

	public function waitUntilDisplayedByClassName($class)
	{
		$this->waitUntil(function() use ($class) {
            $this->assertTrue($this->displayedByClassName($class));
            return true;
        }, 20000);
	}

	public function waitUntilDisplayedByName($name)
	{
		$this->waitUntil(function() use ($name) {
            $this->assertTrue($this->displayedByName($name));
            return true;
        }, 20000);
	}

	public function waitUntilDisplayedById($id)
	{
		$this->waitUntil(function() use ($id) {
            $this->assertTrue($this->displayedById($id));
            return true;
        }, 20000);
	}

	public function waitUntilContainsByCss($selector, $text)
	{
		$this->waitUntil(function() use ($selector, $text) {
            $span = $this->findByCss($selector);
            $this->assertContains($text, $span->text());
            return true;
        }, 20000);
	}

	public function waitUntilContainsByClassName($class, $text)
	{
		$this->waitUntil(function() use ($class, $text) {
            $span = $this->findByClassName($class);
            $this->assertContains($text, $span->text());
            return true;
        }, 20000);
	}

	public function waitUntilAbsentByCss($selector, $time=20000)
	{
        // waitUntil runs till the inner method returns non-null
		$this->waitUntil(function() use ($selector){
            try
            {
                $displayed = $this->execScript('
                                                var element = document.querySelector("'.$selector.'");
                                                if (!element) {
                                                  return 0;
                                                }
                                                return element.getBoundingClientRect().width;
                                              ');
                if ($displayed !== 0)
                {
                    return null;
                }
                else
                {
                    return false;
                }
            }
            catch(\PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e)
            {
                if (strpos($e->getMessage(), 'Unable to locate element:') !== false)
                {
                    return true;
                }
                else
                {
                    throw $e;
                }
            }

            return true;
        }, $time);
	}

	public function selectByNameAndLabel($name, $option)
	{
		$this->select($this->byName($name))->selectOptionByLabel($option);
	}

	public function selectByNameAndValue($name, $option)
	{
		$this->select($this->byName($name))->selectOptionByValue($option);
	}

	public function selectByClassNameAndValue($class, $option)
	{
		$this->select($this->byClassName($class))->selectOptionByValue($option);
	}

	public function selectByClassName($class, $option)
	{
		$this->select($this->byClassName($class))->selectOptionByLabel($option);
	}

	public function waitAndSelectByClassNameAndValue($class, $option)
	{
		$this->waitUntil(function() use($class, $option){
            $this->selectByNameAndValue($class, $option);
            return true;
        }, 20000);
	}

	public function execScript($script)
	{
		return $this->execute(array(
            'script' => $script,
            'args' => array()
        ));
	}

  public function execAsyncScript($script, $timeout)
  {
    $this->timeouts()->asyncScript(20000);
    $script = 'var callback = arguments[0];
               window.setTimeout(function() {
                   callback('.$script.');
               }, '.$timeout.');
            ';
    return $this->executeAsync(array(
            'script' => $script,
            'args'   => array()
        ));
  }
}
