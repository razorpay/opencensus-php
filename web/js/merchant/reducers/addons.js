import ajax from 'merchant/utils/ajax';
import { formatFields } from 'merchant/models/AddOns';
import { merchantFetch } from 'merchant/utils/ajax';
import { makeActionCollectionReducer } from 'merchant/reducers/collection';

export const ADDONS_CREATE = 'ADDONS_CREATE';

// Fn. to create addons
export const saveAddOn = params => {
  const item = formatFields(['name', 'description', 'amount'], params.item);
  const quantity = formatFields('quantity', params.quantity);

  // Prepare exact payload here
  const data = {
    item,
    quantity,
  };

  return {
    type: ADDONS_CREATE,
    payload: merchantFetch({
      method: 'post',
      url: `subscriptions/${params.subscription_id}/addons`,
      data,
    }),
  };
};

// Fetch entire addon list
export const fetchAddOns = params => {
  return merchantFetch({
    url: 'addons',
    params,
  });
};

// Fetch addon list for subscription id
export const fetchSubscriptionAddOns = subscriptionId => {
  return ajax(
    `/subscriptions/${subscriptionId}/addons/due`,
    {},
    '/merchant/api'
  );
};

// Delete addons
export const deleteAddOn = addon_id => {
  return merchantFetch({
    url: `addons/${addon_id}`,
    method: 'delete',
  });
};

// List Reducer
export const addOnsReducer = makeActionCollectionReducer(ADDONS_CREATE);
