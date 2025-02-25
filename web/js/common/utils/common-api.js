import { merchantFetch } from 'merchant/utils/ajax';

const payload = {
  event_type: 'CURRENT_ACCOUNT_INTEREST',
  event_properties: {},
};

export const sendDataToSalesForce = (data, user = {}, mode = 'live') => {
  const userDetails = {
    User_Id: user?.id,
    merchant_id: user?.current,
    name: user?.name,
    email: user?.contact_email,
    contact_mobile: user?.contact_mobile,
    gst_number: user?.gstin || undefined,
    CA_PAN: user?.promoter_pan || undefined,
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
      product_name: 'Current_Account',
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
