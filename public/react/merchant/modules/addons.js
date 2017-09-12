import ajax from 'merchant/utils/ajax';
import AddOns from 'merchant/models/AddOns';
import { makeActionCollectionReducer, fetchAll } from 'rzp/modules/collection';
import { makeEntityReducer, updateEntity } from 'rzp/modules/entity';
import { set } from 'rzp/utils/immutable';
import { formatFields } from 'merchant/resources/addons';

export const ADDONS_FETCH = 'ADDONS_FETCH';
export const ADDONS_CREATE = 'ADDONS_CREATE';
export const ADDONS_EDIT = 'ADDONS_EDIT';
export const ADDONS_DELETE = 'ADDONS_DELETE';

export const fetchAddOns = params => fetchAll(params, AddOns, 'ADDONS');

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

export const deleteAddOn = params => {
  return {
    type: ADDONS_DELETE,
    payload: ajax({
      url: '/addon_delete',
      data: params,
    }),
  };
};

// List Reducer
export const addOnsReducer = makeActionCollectionReducer('ADDONS_FETCH');
