/* eslint valid-jsdoc: 0 */
import ajax from 'common/utils/ajax';
import { BANK_NAMES } from 'newAuth/utils';
import { getChannelID } from '../../../js/merchant/models/GrowthService/commonUtils';

const ENDPOINTS = {
  ORG: '/org',
  ASSESTS: '/v1/growth/assets',
};

const OTP_AUTH_MODE = {
  sms: 'phone number',
  email: 'email',
  sms_and_email: 'phone number and email',
};

export const fetchOrg = () => {
  return ajax({
    url: ENDPOINTS.ORG,
  });
};

export const fetchLoginCards = () => {
  return ajax({
    method: 'post',
    data: {
      asset: 'LOGIN_CARD',
      channel_id: getChannelID('gs_login_card', true),
    },
    url: ENDPOINTS.ASSESTS,
  });
};

/**
 * @param {import("./types").OrgData} data
 * @TODO: Fix banking URL inconsistencies
 * @see https://razorpay.slack.com/archives/CTM086NSF/p1646307229892829
 */
export const transformFetchOrgData = (data) => {
  return {
    logo: data.login_logo_url || 'img/logo_full.png',
    isOrgRZP: data.custom_code === 'rzp',
    orgName: data.custom_code,
    secondFactorAuthMode: OTP_AUTH_MODE[data.second_factor_auth_mode] || 'phone number/email',
    businessName: data.business_name,
    // hiding the signup button for banking URLs from frontend for now until backend fixes the inconsistency
    isSignupAllowed: data.custom_code === 'rzp',
    // Kotak bank's backgroundImageUrl is showing a blank white image
    // thus we are removing it, which will result in fallback of .logo being in use
    backgroundImgUrl: data.custom_code === BANK_NAMES.KKBK ? null : data.background_image_url,
    styles: {
      navBg: data?.merchant_styles?.navBg,
      primary: data?.merchant_styles?.primary,
    },
  };
};

export const transformFetchLoginCardData = (res) => {
  const assetData = [];
  const totalLoginCardLimit = 2;
  if (res?.asset_data) {
    res?.asset_data?.forEach((assetEntry) => {
      assetEntry?.templates?.forEach((template) => {
        assetData.push({ ...template.data, tracking_data: assetEntry.tracking_data });
      });
    });
  } else {
    res?.data?.response?.asset_data?.forEach((assetEntry) => {
      assetEntry?.templates?.forEach((template) => {
        assetData.push({ ...template.data, tracking_data: assetEntry.tracking_data });
      });
    });
  }
  let newAssestDataArray = assetData.map((assetEntry) => {
    const {
      image: { link = '', alt_text = '' } = {},
      cta: { url = '', label = '' } = {},
      description = '',
      title = '',
      type = '',
      id = '',
    } = assetEntry;
    return {
      imgSrc: link,
      imgAlt: alt_text,
      title,
      ctaURL: url,
      desc: description,
      ctaText: label,
      id,
      type,
    };
  });
  if (newAssestDataArray.length)
    newAssestDataArray = newAssestDataArray.slice(0, totalLoginCardLimit);
  return newAssestDataArray;
};
