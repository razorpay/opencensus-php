const ELEMENT_CONSTANTS = {
  VERIFICATION_OTP_MODAL_SELECTOR: 'h3.modal-title >> text="2-Step Verification"',
  SUBMIT_VERIFICATION_MODAL:
    "xpath=//div[@class='Modal__actions']//button[contains(text(),'Confirm')]",
  OTP_VALUE: ['0', '0', '0', '0', '0', '7'],
  VERIFICATION_MODAL_HEADER: '2-Step Verification',
  OTP_TEST_ID: 'otp-input',
};

async function fillOTPModule({ page, position, otp }) {
  // fetching otp field from test id
  const otpField = await page.getByTestId(`${ELEMENT_CONSTANTS.OTP_TEST_ID}-${position + 1}`);
  await otpField.click();
  // filled value in the field
  await otpField.type(otp);
}

export const waitAndProceedVerificationPopup = async ({ page }) => {
  let verificationPopup;
  try {
    // Check for 2 step verification modal using modal selector
    verificationPopup = await page.waitForSelector(
      ELEMENT_CONSTANTS.VERIFICATION_OTP_MODAL_SELECTOR,
      {
        timeout: 5000,
      },
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
      {
        timeout: 5000,
      },
    );

    // applying for verification
    await submitVerification.click();
  }
};
