import { handlePrefix } from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/CustomerSupportDetails/utils';
import {
  InfoDetails,
  OwnerDetailsInterface,
  SubInfoDetails,
  SupportDetailsInterface,
} from 'merchant/views/AccountAndSettings/BusinessSettings/typings';

export const newRoutesMetadata = {
  '/business-settings/contact': {
    name: 'Contact Details',
  },
  '/business-settings/business': {
    name: 'Business Details',
  },
  '/business-settings/gst': {
    name: 'GST Details',
  },
  '/business-settings/customer-support': {
    name: 'Customer Support Details',
  },
  '/business-settings/team': {
    name: 'Manage Team',
  },
};

export const CONTACT_SUPPORT_DETAILS: InfoDetails<SupportDetailsInterface>[] = [
  {
    label: 'Phone Number',
    type: 'phone_number',
    getValue: ({ phone }): string => handlePrefix(phone),
    showDisabledCTA: true,
  },
  {
    label: 'Email',
    type: 'email',
    getValue: ({ email }): string => email,
    showDisabledCTA: true,
  },
  {
    label: 'Website/ Contact Us Link',
    type: 'website',
    getValue: ({ url }): string => url,
    showDisabledCTA: true,
  },
];

export const OWNER_ACCOUNT_DETAILS: InfoDetails<OwnerDetailsInterface>[] = [
  {
    label: 'Name',
    type: 'name',
    getValue: ({ contact_name }: OwnerDetailsInterface): string => contact_name,
    showDisabledCTA: false,
  },
  {
    label: 'Email',
    type: 'email',
    getValue: ({ contact_email }: OwnerDetailsInterface): string => contact_email,
    showDisabledCTA: false,
  },
  {
    label: 'Phone Number',
    type: 'phone',
    getValue: ({ contact_mobile }: OwnerDetailsInterface): string => contact_mobile,
    showDisabledCTA: false,
  },
];

export const USER_ACCOUNT_DETAILS: Record<string, SubInfoDetails> = {
  display_name: {
    label: 'Display Name',
    showDisabledCTA: true,
    type: 'display_name',
  },
  name: {
    label: 'Name',
    showDisabledCTA: false,
    type: 'name',
  },
  email: {
    label: 'Email',
    showDisabledCTA: true,
    type: 'email',
  },
  contact_mobile: {
    label: 'Phone Number',
    showDisabledCTA: true,
    type: 'contact_mobile',
  },
};
