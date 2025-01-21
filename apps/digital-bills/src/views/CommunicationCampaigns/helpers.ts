import { COMMUNICATION_CAMPAIGN_OPTIONS, ListingOptions } from './constants';

/**
 * Function to get active page from route location pathname
 * @param {String} pathname
 * @return {String} active page name
 */
export const getActiveListingFromRoute = (pathname: string): ListingOptions => {
  if (pathname.startsWith('/auto-engage/sms')) return COMMUNICATION_CAMPAIGN_OPTIONS.SMS;
  if (pathname.startsWith('/auto-engage/email')) return COMMUNICATION_CAMPAIGN_OPTIONS.EMAIL;
  if (pathname.startsWith('/auto-engage/whatsApp')) return COMMUNICATION_CAMPAIGN_OPTIONS.WHATSAPP;
  return COMMUNICATION_CAMPAIGN_OPTIONS.SMS;
};
