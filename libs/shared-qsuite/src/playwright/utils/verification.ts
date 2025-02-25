import { Locator, ElementHandle } from '@playwright/test';
import { ExtendedPage as Page } from './base';

const ELEMENT_CONSTANTS = {
  VERIFICATION_OTP_MODAL_SELECTOR: 'h3.modal-title >> text="2-Step Verification"',
  SUBMIT_VERIFICATION_MODAL:
    "xpath=//div[@class='Modal__actions']//button[contains(text(),'Confirm')]",
  OTP_VALUE: ['0', '0', '0', '0', '0', '7'],
  VERIFICATION_MODAL_HEADER: '2-Step Verification',
  OTP_TEST_ID: 'otp-input',
};

// Define the function to fill OTP in a specific field
async function fillOTPModule({
  page,
  position,
  otp,
}: {
  page: Page;
  position: number;
  otp: string;
}): Promise<void> {
  // fetching otp field from test id
  const otpField: Locator = await page.getByTestId(
    `${ELEMENT_CONSTANTS.OTP_TEST_ID}-${position + 1}`,
  );
  await otpField.click();
  // filled value in the field
  await otpField.type(otp);
}

// Define the function to handle the verification popup
export const waitAndProceedVerificationPopup = async ({ page }: { page: Page }): Promise<void> => {
  let verificationPopup: ElementHandle<SVGElement | HTMLElement> | null;

  try {
    // Check for 2 step verification modal using modal selector
    verificationPopup = await page.waitForSelector(
      ELEMENT_CONSTANTS.VERIFICATION_OTP_MODAL_SELECTOR,
    );
  } catch (err) {
    // if no verificationPopup then proceed to next step
    verificationPopup = null;
  }

  // if verificationPopup showed up then perform otp form
  if (verificationPopup) {
    // iterate through all otp fields
    for (const [position, otp] of ELEMENT_CONSTANTS.OTP_VALUE.entries()) {
      // fill the value in the otp form field
      // eslint-disable-next-line no-await-in-loop
      await fillOTPModule({ page, position, otp });
    }

    // after filling the values in the otp form, submit the otp for verification

    // fetching submit button cta
    const submitVerification = await page.waitForSelector(
      ELEMENT_CONSTANTS.SUBMIT_VERIFICATION_MODAL,
    );

    // applying for verification
    await submitVerification.click();
  }
};
