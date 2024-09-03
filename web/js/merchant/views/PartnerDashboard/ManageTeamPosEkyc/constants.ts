import { BadgeProps } from '@razorpay/blade/components';
import { CountryCodeType, isValidPhoneNumber } from '@razorpay/i18nify-js';
import * as yup from 'yup';

import rolesList from 'merchant/helpers/permissions/roles-list';

import { InvitationStatusT } from './types';

export const roleToDisplayMap = {
  // only one role present for v1 of POS eKYC
  [rolesList.PARTNER_AGENT]: 'POS Agent',
};

export const statusColorMap: Record<InvitationStatusT, BadgeProps['color']> = {
  awaiting: 'information',
  onboarded: 'positive',
};

export const statusToDisplayMap: Record<InvitationStatusT, string> = {
  awaiting: 'Awaiting',
  onboarded: 'Onboarded',
};

export const getInviteMemberValidation = ({ countryCode }: { countryCode: CountryCodeType }) =>
  yup.object().shape({
    name: yup.string().required('Name is required'),
    contactMobile: yup
      .string()
      .required('Phone number is required')
      .test('len', 'Phone number entered is invalid', (val) => {
        return !!val && isValidPhoneNumber(val, countryCode);
      }),
    role: yup.string().required('Please select a role'),
  });
