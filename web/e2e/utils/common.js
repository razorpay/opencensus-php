import { COMMON_SELECTORS } from './selectors';

export const loginByMobile = async ({ page, mobile }) => {
  await page.click('input[type="text"]');
  await page.fill('input[type="text"]', mobile);
  await page.click('text="Next"');
  await page.click('input[id="Enter OTP"]');
  await page.fill('input[id="Enter OTP"]', '000007');
  await Promise.all([page.waitForNavigation(), page.click('text="Login"')]);
};

export const loginByEmail = async ({ page, cred }) => {
  await page.click('input[type="text"]');
  await page.fill('input[type="text"]', cred.username);
  await page.click('text="Next"');
  await page.click('input[type="password"]');
  await page.fill('input[type="password"]', cred.password);
  await Promise.all([page.waitForNavigation(), page.click('text="Login"')]);
};

export const wait = (ms) => new Promise((res) => setTimeout(() => res(), ms));

// the toggle switch web/js/common/ui/Forms/SwitchField.js expects the
// on click event to have some page x and page y movement, therefore need to stimulate it
export const mouseClickToggleSwitch = async ({ page, container = page }) => {
  await page.waitForTimeout(5000);
  const button = await container.locator(COMMON_SELECTORS.toggleSwitch);
  // switch knob is expecting some value for event.pageX and event.pageY, therefore stimulating mouse movement
  const { x, y } = await button.boundingBox();
  await page.mouse.click(x + 10, y + 10, { button: 'left', clickCount: 1 });
};
