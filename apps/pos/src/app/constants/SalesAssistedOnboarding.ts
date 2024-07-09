import { MerchantRegistrationError } from '../types/SalesAssistedOnboarding';

export const MERCHANT_REGISTRATION_ERRORS: Record<string, MerchantRegistrationError> = {
  BAD_REQUEST_CONTACT_MOBILE_ALREADY_EXISTS: {
    title: 'Already Existing Account',
    description: `This service is for new accounts only. We're working on bringing it to existing accounts soon!`,
  },
  INVALID_PHONE_NUMBER: {
    title: 'Invalid Phone Number',
    description: 'Please enter a valid phone number',
  },
  GENERIC_ERROR: {
    title: 'Something went wrong',
    description: 'Please try again',
  },
};
