import {
  BILL_CAMPAIGN_OPTIONS,
  ListingOptions,
} from '@apps/digital-bills/src/views/BillCampaigns/constants';

/**
 * Function to get active page from route location pathname
 * @param {String} pathname
 * @return {String} active page name
 */
export const getActiveListingFromRoute = (pathname: string): ListingOptions => {
  if (pathname.startsWith('/auto-engage/bannerInBill')) return BILL_CAMPAIGN_OPTIONS.BANNER_IN_BILL;
  if (pathname.startsWith('/auto-engage/adBelowBill')) return BILL_CAMPAIGN_OPTIONS.AD_BELOW_BILL;
  if (pathname.startsWith('/auto-engage/surveyInBill'))
    return BILL_CAMPAIGN_OPTIONS.ADD_SURVEY_BUTTON;
  if (pathname.startsWith('/auto-engage/sellBelowBill'))
    return BILL_CAMPAIGN_OPTIONS.SELL_BELOW_BILL;
  if (pathname.startsWith('/auto-engage/popupOverBill'))
    return BILL_CAMPAIGN_OPTIONS.POPUP_OVER_BILL;
  return BILL_CAMPAIGN_OPTIONS.BANNER_IN_BILL;
};
