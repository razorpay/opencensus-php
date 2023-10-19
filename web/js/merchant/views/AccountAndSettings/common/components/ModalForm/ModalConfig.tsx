import React from 'react';
import { PersonalProfileFields } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import { Text } from '@razorpay/blade/components';
import { isPhone, isEmail } from 'common/utils/validators';
import { Store } from 'common/typings';

const { DISPLAY_NAME, NAME, EMAIL, CONTACT_MOBILE } = PersonalProfileFields;
type ModalConfigKey = typeof DISPLAY_NAME | typeof NAME | typeof EMAIL | typeof CONTACT_MOBILE;

interface EntityConfigI {
  title: string;
  label: string;
  bodyText: string;
  ctaText?: string;
  checkboxText?: JSX.Element;
  isValid?: (input: string) => boolean;
  errorText: string;
}

export const modalConfig = ({
  user,
}: {
  user: Store['session']['user'];
}): Record<ModalConfigKey, EntityConfigI> => ({
  display_name: {
    title: 'Edit display name',
    label: 'Enter new display name',
    bodyText:
      'The new display name will reflect immediately on your dashboard after you update it. It will be visible to you and your team on the Razorpay dashboard.',
    errorText: 'Invalid display name',
  },
  name: {
    title: 'Edit profile name',
    label: 'Enter new profile name',
    bodyText:
      'The new profile name will reflect immediately on your dashboard after you update it. It will be visible to you in your profile section',
    errorText: 'Invalid profile name',
    isValid: (name) => !!name && name.length >= 4,
  },
  email: {
    title: 'Edit account email',
    label: 'Enter new account email',
    bodyText:
      'You will have to verify the above entered new account email on the next step. After verifying, the email will be changed immediately.',
    checkboxText: (
      <>
        <Text size="small">
          Use this ID as my contact email and receive all Razorpay related communication to this ID
        </Text>
        <Text size="small" color="surface.text.muted.lowContrast">
          Currently your contact email has been set to {user.user?.email}
        </Text>
      </>
    ),
    ctaText: 'Continue',
    isValid: (email) => !!email && isEmail(email),
    errorText: 'Invalid account email',
  },
  contact_mobile: {
    title: 'Edit mobile number',
    label: 'Enter new mobile number',
    bodyText:
      'You will have to verify the above entered new mobile number on the next step. After verifying, the mobile number will be changed immediately.',
    ctaText: 'Continue',
    isValid: (contactMobile) => !!contactMobile && isPhone(contactMobile),
    errorText: 'Invalid mobile number',
  },
});
