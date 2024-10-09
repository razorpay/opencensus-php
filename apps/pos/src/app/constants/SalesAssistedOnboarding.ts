import { MerchantRegistrationError, StatusTile } from '../types/SalesAssistedOnboarding';

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
    title: 'Error occured!',
    description: 'Something went wrong. Please try again',
  },
};

export const StatusTiles: StatusTile[] = [
  {
    name: 'KYC Qualified',
    key: 'kycQualifiedStb',
  },
  {
    name: 'Pending',
    key: 'pending',
  },

  {
    name: 'Under Review',
    key: 'underReview',
  },
  {
    name: 'Activated',
    key: 'activated',
  },

  {
    name: 'Needs Clarification',
    key: 'needsClarification',
  },
  {
    name: 'Rejected',
    key: 'rejected',
  },
];

export const ONLY_NUMBER_REGEX = /^\d+(\.\d+)?$/;

export const PARTNER_ASSISTED_ONBOARDING = 'PARTNER_ASSISTED_ONBOARDING';
export const ASSISTED_ONBOARDING = 'ASSISTED_ONBOARDING';
