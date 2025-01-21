import type { BreadCrumbType } from '@apps/digital-bills/src/common/components/Breadcrumbs/types';

type PageRoutesType = {
  [prop: string]: {
    link: string;
    label: string;
  };
};

export const COMMUNICATION_CAMPAIGN_OPTIONS = {
  SMS: 'sms',
  EMAIL: 'email',
  WHATSAPP: 'whatsApp',
} as const;

type Listings = typeof COMMUNICATION_CAMPAIGN_OPTIONS;
export type ListingOptions = Listings[keyof Listings];

export const PAGE_ROUTES: PageRoutesType = {
  [COMMUNICATION_CAMPAIGN_OPTIONS.SMS]: {
    link: '/auto-engage/sms',
    label: 'SMS',
  },
  [COMMUNICATION_CAMPAIGN_OPTIONS.EMAIL]: {
    link: '/auto-engage/email',
    label: 'E-Mail',
  },
  [COMMUNICATION_CAMPAIGN_OPTIONS.WHATSAPP]: {
    link: '/auto-engage/whatsApp',
    label: 'WhatsApp',
  },
};

export const PAGE_BREADCRUMBS: BreadCrumbType[] = [
  { label: 'BillMe' },
  { label: 'Communication Campaigns' },
];
