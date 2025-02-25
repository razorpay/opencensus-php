import setCookie from '../setCookie';
import readCookie from '../readCookie/readCookie';

const referralParamsToPayloadMap = {
  utm_campaign: 'utmCampaign',
  utm_source: 'utmSource',
  utm_content: 'utmContent',
  utm_medium: 'utmMedium',
  referralCode: 'referralCode',
};

export const getReferralParams = () => {
  const referralParams = readCookie('referral_params');
  if (!referralParams) {
    return {};
  }
  return JSON.parse(referralParams);
};

export const getReferralPayload = () => {
  const referralParams = getReferralParams();
  const referralPayload = {};
  for (const [paramKey, payloadKey] of Object.entries(referralParamsToPayloadMap)) {
    if (referralParams[paramKey]) {
      referralPayload[payloadKey] = referralParams[paramKey];
    }
  }
  return referralPayload;
};

export const setReferralParams = (locationQuery) => {
  if (locationQuery.get('utm_source') !== 'friendbuy') {
    return;
  }
  const referralParams = Object.keys(referralParamsToPayloadMap).reduce((params, param) => {
    const value = locationQuery.get(param);
    if (!!value) {
      params[param] = value;
    }
    return params;
  }, {});
  setCookie('referral_params', JSON.stringify(referralParams));
};
