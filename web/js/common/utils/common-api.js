import { merchantFetch } from 'merchant/utils/ajax';

const payload = {
  event_type: 'CURRENT_ACCOUNT_INTEREST',
  event_properties: {},
};

export const sendDataToSalesForce = (data, user, mode = 'live') => {
  const eventPropertiesMap = {
    'LOC-Cross-sell-V1': {
      merchant_id: user.current,
      name: user.name,
      email: user.contact_email,
      contact_mobile: user.contact_mobile,
      Campaign_ID: 'LOC-Cross-sell-V1',
      product_name: 'LOC',
    },
  };

  if (typeof data === 'string') payload.event_properties = eventPropertiesMap[data] || {};
  else if (typeof data === 'object' && data !== null) payload.event_properties = data;

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
