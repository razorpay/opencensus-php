import { merchantFetch } from 'merchant/utils/ajax';

const payload = {
  event_type: 'CURRENT_ACCOUNT_INTEREST',
  event_properties: {},
};

export const sendDataToSalesForce = (data, user = {}, mode = 'live') => {
  const userDetails = {
    merchant_id: user?.current,
    name: user?.name,
    email: user?.contact_email,
    contact_mobile: user?.contact_mobile,
  };

  const eventPropertiesMap = {
    'LOC-Cross-sell-V1': {
      Campaign_ID: 'LOC-Cross-sell-V1',
      product_name: 'LOC',
    },
    'capital-whats-new': {
      Campaign_ID: 'capital-whats-new',
      product_name: 'CARDS',
    },
    'ultra-campaign': {
      Campaign_ID: 'Ultra-CC',
      product_name: 'Cards',
    },
    'ultra-campaign-p2-cash-advance': {
      Campaign_ID: 'Ultra-LOC',
      product_name: 'LOC',
    },
    'connected-banking-icici': {
      Campaign_ID: 'icici_account_linking',
      product_name: 'CA',
    },
  };

  if (typeof data === 'string')
    payload.event_properties = { ...userDetails, ...(eventPropertiesMap[data] || []) } || {};
  else if (typeof data === 'object' && data !== null)
    payload.event_properties = { ...userDetails, ...data };

  return merchantFetch({
    url: `merchant/${user.current}/salesforce_event`,
    mode,
    method: 'post',
    data: payload,
    headers: {
      'Content-Type': 'application/json',
    },
  });
};

// TODO: Need to update this definition in banner carousel improvement for v1 release
export const evaluateSplitz = (mid = 'G5KWPzRBj0ysa0', experiment_id = 'IffQdDSr2O36Bm') => {
  // TODO: remvoe default value

  return merchantFetch({
    url: `splitz/evaluate`,
    data: {
      mid,
      experiment_id,
    },
    method: 'post',
    // mode: 'live', // TODO: Ask shivam
    headers: {
      'Content-Type': 'application/json',
    },
  })
    .then((response) => {
      console.log('campaignId');
      const { data: { response: { variant = [] } = {} } = {} } = response;
      const campaignId =
        variant?.[0].key.toLowerCase() === 'campaign_id'
          ? variant?.[0].value
          : 'Platform Growth - CA Awareness';
      sendDataToSalesForce(
        {
          Campaign_ID: campaignId,
          product_name: 'Current_Account',
        },
        {},
      );
    })
    .catch((_) => console.log('error'));
};
