import { trackhubsContactUpdate, fireAnalyticsEvents } from 'common/utils/googleAnalytics';
import BingDataObj from 'common/utils/bingDataObj';
import { getCookie } from 'common/utils/cookies';

import { addPrefixToObjectKeys, isPresent } from 'common/utils/rzp-utils';

import { trackL1FormSuccess, trackL1FormError, trackSubmit } from './ga_new';
import { fireActFBEvents, fireKYCFBEvents } from './fb';

import { BUSINESS_TYPE_OPTIONS } from './L1FormMap';

export function fireL1FormSuccessEvents(user) {
  let data = new BingDataObj('activationform', 'complete', 'success', 1);
  updateHubSpotContactsProperties(
    {
      ...data,
      activation_flow: user.activation_flow,
      completed: true,
    },
    {},
    'l1_',
  );
  fireAnalyticsEvents({
    bingData: data,
    liData: 987404,
    twiData: 'o1ua0',
    quoraData: 'AddToWishlist',
    redditData: 'AddToWishlist',
  });
  fireActFBEvents(user);
  trackL1FormSuccess(user);
}

export function fireL1FormErrorEvents() {
  trackL1FormError();
  let dataError = new BingDataObj('activationform', 'complete', 'error', 1);
  fireAnalyticsEvents({
    fbData: 'activation_complete_error',
    bingData: dataError,
    liData: 987412,
    twiData: 'o1ua2',
  });
}

export function fireFormStartEvents(isL1Completed = false) {
  const fbData = isL1Completed ? 'KYC_start' : 'activation_start';
  const liData = isL1Completed ? 987420 : 987396;
  const twiData = isL1Completed ? 'o1ua3' : 'o1u9z';
  const prefix = isL1Completed ? 'l2_' : 'l1_';

  fireAnalyticsEvents({
    fbData,
    liData,
    twiData,
  });

  updateHubSpotContactsProperties(
    {
      started: true,
    },
    {
      hs_google_click_id: getCookie('gclid'),
    },
    prefix,
  );
}

export function fireKYCSubmitEvents(data) {
  if (data.error) {
    trackSubmit(data);
    return;
  }

  const compAllData = new BingDataObj('kycform', 'complete', 'all', 1);

  fireAnalyticsEvents({
    bingData: compAllData,
    liData: 987452,
    twiData: 'o1ua7',
    quoraData: 'AddToCart',
    redditData: 'AddToCart',
  });
  updateHubSpotContactsProperties(
    {
      final_submission: true,
    },
    {
      account_status: data.activation_status,
    },
  );

  const activationFlow = data.activation_flow;

  if (activationFlow === 'whitelist' || activationFlow === 'greylist') {
    const conversionId = activationFlow === 'whitelist' ? 987436 : 987428;
    const txnId = activationFlow === 'whitelist' ? 'o1ua5' : 'o1ua4';
    const bingData = new BingDataObj('kycform', 'complete', activationFlow, 1);
    fireAnalyticsEvents({
      bingData: bingData,
      liData: conversionId,
      twiData: txnId,
    });
  }

  fireKYCFBEvents(data);

  trackSubmit({
    type: true,
    isUnregisteredBusiness: data.isUnregisteredBusiness,
    activationFlow,
  });
}

export function updateHubSpotContactsProperties(data, extra = {}, prefix = 'l2_') {
  const hbsData = addPrefixToObjectKeys(prefix, data);
  const trackData = {
    ...hbsData,
    ...extra,
  };
  const businessTypeKey = `${prefix}_business_type`;
  const promoterPanKey = `${prefix}_promoter_pan`;
  const gstinKey = `${prefix}_gstin`;

  if (data.business_type) {
    trackData[businessTypeKey] = (
      BUSINESS_TYPE_OPTIONS.find((e) => e.name == data.business_type) || {}
    ).label;
  }

  if (data.promoter_pan) {
    trackData[promoterPanKey] = !!trackData[promoterPanKey];
  }

  if (data.gstin) {
    trackData[gstinKey] = !!trackData[gstinKey];
  }

  trackhubsContactUpdate(trackData);
}
