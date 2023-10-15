import { PersonalProfileFields } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';

type DISPLAYNAME = PersonalProfileFields.DISPLAY_NAME;
type NAME = PersonalProfileFields.NAME;

interface EntityConfigI {
  title: string;
  label: string;
  bodyText: string;
}

export const modalConfig: Record<DISPLAYNAME | NAME, EntityConfigI> = {
  display_name: {
    title: 'Edit display name',
    label: 'Enter new display name',
    bodyText:
      'The new display name will reflect immediately on your dashboard after you update it. It will be visible to you and your team on the Razorpay dashboard.',
  },
  name: {
    title: 'Edit profile name',
    label: 'Enter new profile name',
    bodyText:
      'The new profile name will reflect immediately on your dashboard after you update it. It will be visible to you in your profile section',
  },
};
