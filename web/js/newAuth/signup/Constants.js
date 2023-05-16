import * as Yup from 'yup';

export const STEPS = {
  MOBILE_NUMBER: 1,
  MOBILE_VERIFICATION: 2,
  CONTACT_INFO: 3,
  BUSINESS_TYPE_SELECTION: 4,
  PARTNER_TYPE_SELECTION: 5,
  CONGRATS: 6,
  EMAIL_VERIFICATION: 7,
};

const {
  MOBILE_NUMBER,
  MOBILE_VERIFICATION,
  CONTACT_INFO,
  BUSINESS_TYPE_SELECTION,
  PARTNER_TYPE_SELECTION,
  CONGRATS,
  EMAIL_VERIFICATION,
} = STEPS;

export const STEP_TO_PROGRESS_WIDTH = {
  [MOBILE_NUMBER]: '0',
  [MOBILE_VERIFICATION]: '20',
  [CONTACT_INFO]: '40',
  [BUSINESS_TYPE_SELECTION]: '60',
  [PARTNER_TYPE_SELECTION]: '80',
  [CONGRATS]: '100',
  [EMAIL_VERIFICATION]: '100',
};

export const SCREEN_NAME = {
  [MOBILE_NUMBER]: 'Mobile Number',
  [MOBILE_VERIFICATION]: 'Mobile Number OTP Verify',
  [CONTACT_INFO]: 'Contact Details',
  [BUSINESS_TYPE_SELECTION]: 'Business Type Screen',
  [PARTNER_TYPE_SELECTION]: 'Partner Type Screen',
  [EMAIL_VERIFICATION]: 'Email Verify OTP',
  [CONGRATS]: 'Congrats Screen',
};

export const MOBILE_MAX_OTP_TRIES = 5;
export const EMAIL_MAX_OTP_TRIES = 5;
export const MOBILE_RESEND_OTP_COUNTDOWN = 120;
export const EMAIL_RESEND_OTP_COUNTDOWN = 120;

export const mobileNumberSchema = Yup.object().shape({
  mobileNumber: Yup.string()
    .trim()
    .matches(/^[\d]{10}$/, {
      message: 'Enter 10 digit mobile number',
      excludeEmptyString: true,
    })
    .required('Required Field'),
});

export const mobileVerificationSchema = Yup.object().shape({
  otp: Yup.string()
    .trim()
    .matches(/^[\d]{6}$/, {
      message: 'Enter 6 digit OTP',
      excludeEmptyString: true,
    })
    .required('Required Field'),
});

export const businessTypeSelectionSchema = Yup.object().shape({
  businessType: Yup.string().required('Please select a business type'),
});

export const contactInfoSchema = Yup.object().shape({
  contactName: Yup.string()
    .trim()
    .matches(/^[a-zA-Z\s]+$/, {
      message: 'Enter valid contact name',
      excludeEmptyString: true,
    })
    .min(3, 'Name must be of atleast 3 characters')
    .required('Required Field'),
});

export const congratsFormSchema = Yup.object().shape({
  contactEmail: Yup.string().email('Enter a valid Email ID').nullable(),
});

export const emailVerificationSchema = Yup.object().shape({
  emailOtp: Yup.string()
    .trim()
    .matches(/^[\d]{6}$/, {
      message: 'Enter 6 digit OTP',
      excludeEmptyString: true,
    })
    .required('Required Field'),
});

export const partnerTypeSelectionSchema = Yup.object().shape({
  partnerType: Yup.string().required('Please select a partner type'),
});

export const MOBILE_INCORRECT_OTP_ERROR_DESC = 'Verification failed because of incorrect OTP.';
export const EMAIL_INCORRECT_OTP_ERROR_DESC = 'Verification failed because of incorrect OTP.';
export const LOW_CAPTCHA_SCORE = 'Low captcha score';
export const CAPTCHA_FAILED = 'Captcha Failed';
export const V3 = 'v3';
export const EMAIL_ALREADY_TAKEN_ERROR_DESC = 'That email is already taken.';
