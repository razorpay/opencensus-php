import ajax from 'merchant/utils/ajax';
import AddOns from 'merchant/models/AddOns';
import { makeActionCollectionReducer, fetchAll } from 'rzp/modules/collection';
import { makeEntityReducer, updateEntity } from 'rzp/modules/entity';
import { set } from 'rzp/utils/immutable';
import { formatFields } from 'merchant/resources/addons';
import { merchantFetch } from 'rzp/utils/ajax';

export const ADDONS_CREATE = 'ADDONS_CREATE';
export const ADDONS_EDIT = 'ADDONS_EDIT';

// Fn. to create / edit add ons
export const saveAddOn = (params, isNew = true) => {
  const item = formatFields(['name', 'description', 'amount'], params.item);
  const quantity = formatFields('quantity', params.quantity);

  // Prepare exact payload here
  const data = {
    item,
    quantity,
  };

  return {
    type: isNew ? ADDONS_CREATE : ADDONS_EDIT,
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
