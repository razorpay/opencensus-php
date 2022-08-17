import { getCookie, setCookie } from 'common/utils/cookies';

export const utmSourceMap = {
  PG: 'pg',
};

export const utmMediumMap = {
  DASHBOARD: 'dashboard',
};

export const utmCampaignMap = {
  APP_SWITCHER: 'app_switcher',
  BANKING_WIDGET: 'banking_widget',
  ACCOUNT_LINKING: 'account_linking',
  NITRO: 'nitro',
};

// these are consumed by X BE after redirecting to X
export const updateUtmParams = (newParams = {}) => {
  const RZP_UTM = 'rzp_utm';
  const rzpUtmCookie = getCookie(RZP_UTM);

  const rzpUtm = rzpUtmCookie ? JSON.parse(rzpUtmCookie) : {};
  const attributions = rzpUtm.attributions || [];
  const attributionsLength = attributions.length;

  /*
    rzp_utm attributions would be either 1 or 2 in count if present. 
    They shouldn't be more than 2. Update the second one if it already exists
    If rzp_utm doesn't exist attributes would be 0 as well
  */
  const shouldUpdateExistingAttribution = attributionsLength >= 2;
  const newAttributionObj = {
    ...(shouldUpdateExistingAttribution ? attributions[1] : {}),
    ...newParams,
  };
  const finalAttributions =
    attributions.length >= 1 ? [attributions[0], newAttributionObj] : [newAttributionObj];
  const updatedRzpUtm = { ...rzpUtm, attributions: finalAttributions };

  const updatedCookie = JSON.stringify(updatedRzpUtm);

  const isProd = window.location.hostname.endsWith('razorpay.com');
  const domain = isProd ? 'razorpay.com' : 'razorpay.in';

  const expiryDays = 365;
  const date = new Date();
  date.setDate(date.getDate() + expiryDays);
  const expires = date.toUTCString();

  setCookie(RZP_UTM, updatedCookie, expires, '/', domain);
};
