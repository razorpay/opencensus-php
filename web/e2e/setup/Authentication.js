const { credentials } = require('../utils/constants');
// Login url with credentials

class Login {
  constructor(page, baseUrl = '') {
    this.page = page;
    this.baseUrl = baseUrl;
  }

  async applyLoginForm() {
    await this.page.click('input[type="text"]');
    await this.page.fill('input[type="text"]', credentials.username);
    await this.page.click('text="Next"');
    await this.page.click('input[type="password"]');
    await this.page.fill('input[type="password"]', credentials.password);
  }

  async login() {
    await this.page.goto(`${this.baseUrl}/?screen=sign_in`);
    await this.applyLoginForm();
    await Promise.all([
      this.page.waitForNavigation({ timeout: 250000 }),
      this.page.click('text="Login"'),
    ]);
  }
}

module.exports = {
  Login,
};
