import ajax from 'merchant/utils/ajax';
import AddOns from 'merchant/models/AddOns';
import { makeActionCollectionReducer, fetchAll } from 'rzp/modules/collection';
import { makeEntityReducer, updateEntity } from 'rzp/modules/entity';
import { set } from 'rzp/utils/immutable';
import { formatFields } from 'merchant/resources/addons';

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
    payload: ajax({
      url: '/user/generic',
      method: 'post',
      appendModeInURL: false,
      appendModeInQueryParam: true,
      data: {
        route_name: 'subscription_create_addon',
        url_params: JSON.stringify({
          '{subscription_id}': params.subscription_id,
        }),
        body: {
          ...data,
        },
      },
    }),
  };
};

// Fetch entire addon list
export const fetchAddOns = params => {
  const data = {
    route_name: 'addon_fetch_multiple',
  };

  if (params) {
    data.query_params = JSON.stringify(params);
  }

  return ajax({
    url: 'user/generic',
    appendModeInURL: false,
    appendModeInQueryParam: true,
    data,
  });
};

// Fetch addon list for subscription id
export const fetchSubscriptionAddOns = subscriptionId => {
  return ajax({
    url: '/user/generic',
    appendModeInURL: false,
    appendModeInQueryParam: true,
    data: {
      route_name: 'addons_fetch_due',
      url_params: JSON.stringify({
        '{subscription_id}': subscriptionId,
      }),
    },
  });
};

// Delete addons
export const deleteAddOn = addon_id => {
  return ajax({
    method: 'delete',
    url: 'user/generic',
    appendModeInURL: false,
    appendModeInQueryParam: true,
    data: {
      route_name: 'addon_delete',
      url_params: JSON.stringify({
        '{addon_id}': addon_id,
      }),
    },
  });
};

// List Reducer
export const addOnsReducer = makeActionCollectionReducer(ADDONS_CREATE);
