const PlaywrightEnvironment = require('jest-playwright-preset/lib/PlaywrightEnvironment').default;

class CustomEnvironment extends PlaywrightEnvironment {
  async setup() {
    await super.setup();
  }

  async teardown() {
    // Your teardown
    await super.teardown();

    // TODO: Trigger webhooks with the errors
  }

  async handleTestEvent(event) {
    if (event.name === 'test_done' && event.test.errors.length > 0) {
      // Take a screenshot if errors are present
      const parentName = event.test.parent.name.replace(/\W/g, '-');
      const specName = event.test.name.replace(/\W/g, '-');

      console.log('Errors:', event.test.errors);
      console.log('Please check browser console errors');

      await this.global.page.screenshot({
        path: `screenshots/${parentName}_${specName}.png`,
      });
    }
  }
}

module.exports = CustomEnvironment;
