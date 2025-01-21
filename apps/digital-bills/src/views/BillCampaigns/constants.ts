import type { BreadCrumbType } from '@apps/digital-bills/src/common/components/Breadcrumbs/types';

type PageRoutesType = {
  [prop: string]: {
    link: string;
    label: string;
  };
};

export const BILL_CAMPAIGN_OPTIONS = {
  BANNER_IN_BILL: 'bannerInBill',
  AD_BELOW_BILL: 'adBelowBill',
  ADD_SURVEY_BUTTON: 'addSurveyButton',
  SELL_BELOW_BILL: 'sellBelowBill',
  POPUP_OVER_BILL: 'popupOverBill',
} as const;

type Listings = typeof BILL_CAMPAIGN_OPTIONS;
export type ListingOptions = Listings[keyof Listings];

export const PAGE_ROUTES: PageRoutesType = {
  [BILL_CAMPAIGN_OPTIONS.BANNER_IN_BILL]: {
    link: '/auto-engage/bannerInBill',
    label: 'Banner In Bill',
  },
  [BILL_CAMPAIGN_OPTIONS.AD_BELOW_BILL]: {
    link: '/auto-engage/adBelowBill',
    label: 'Ad Below Bill',
  },
  [BILL_CAMPAIGN_OPTIONS.ADD_SURVEY_BUTTON]: {
    link: '/auto-engage/surveyInBill',
    label: 'Add Survey Button',
  },
  [BILL_CAMPAIGN_OPTIONS.SELL_BELOW_BILL]: {
    link: '/auto-engage/sellBelowBill',
    label: 'Sell Below Bill',
  },
  [BILL_CAMPAIGN_OPTIONS.POPUP_OVER_BILL]: {
    link: '/auto-engage/popupOverBill',
    label: 'Popup over Bill',
  },
};

export const PAGE_BREADCRUMBS: BreadCrumbType[] = [
  { label: 'BillMe' },
  { label: 'Bill Campaigns' },
];
