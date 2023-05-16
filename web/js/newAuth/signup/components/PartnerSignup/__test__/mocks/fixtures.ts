export const emailOtpResponse = {
  status_code: 200,
  success: true,
  data: { token: 'Faked' },
};
export const emailOtpErrorResponse = {
  status_code: 400,
  success: false,
  errors: ['That email is already taken.', 'Status Code: 400'],
};

export const mobileNumberResponse = {
  status_code: 200,
  success: true,
  data: { token: 'Faked' },
};
export const userRegisterOtpErrorResponse = {
  status_code: 400,
  success: false,
  errors: [{ internal_error_code: 'BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS' }],
};

export const mobileNumberVerificationResponse = {
  status_code: 200,
  success: true,
  data: { token: 'Faked', id: '123', user_id: '111' },
};

export const userWhatsappOptInResponse = {
  status_code: 200,
  success: true,
  data: {},
};
