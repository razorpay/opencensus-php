import AddOns from 'merchant/models/AddOns';
import { makeActionCollectionReducer, fetchAll } from 'rzp/modules/collection';
import { makeEntityReducer, updateEntity } from 'rzp/modules/entity';
import { set } from 'rzp/utils/immutable';

export const PLANS_FETCH = 'ADDONS_FETCH';
export const PLAN_CREATE = 'ADDONS_CREATE';
export const PLAN_EDIT = 'ADDONS_EDIT';
export const PLAN_DELETE = 'ADDONS_DELETE';

export const fetchAddOns = params => fetchAll(params, AddOns, 'ADDONS');

export const saveAddOn = params => {
  const plan = new Plan(params);
  return {
    type: plan.isNew ? PLAN_CREATE : PLAN_EDIT,
    payload: plan.save(),
  };
};

export const deleteAddOn = params => {
  const plan = new Plan(params);

  return {
    type: PLAN_DELETE,
    payload: plan.delete(),
    id: plan.id,
  };
};

// List Reducer
export const addOnsReducer = makeActionCollectionReducer('ADDONS_FETCH');
