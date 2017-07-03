import Plan from 'merchant/models/Plan';
import { makeActionCollectionReducer, fetchAll } from 'rzp/modules/collection';
import { makeEntityReducer, updateEntity } from 'rzp/modules/entity';

const PLANS_FETCH = 'PLANS_FETCH';
const PLAN_CREATE = 'PLAN_CREATE';
const PLAN_EDIT = 'PLAN_EDIT';
const PLAN_DELETE = 'PLAN_DELETE';
const PLAN_FETCH = 'PLAN_FETCH';

export const fetchPlans = params => fetchAll(params, Plan, 'PLANS');

export const fetchPlan = id => {
  let plan = new Plan();
  return {
    type: PLAN_FETCH,
    payload: plan.fetch(id),
  };
};

export const savePlan = params => {
  const plan = new Plan(params);
  return {
    type: plan.isNew ? PLAN_CREATE : PLAN_EDIT,
    payload: plan.save(),
  };
};

export const deletePlan = params => {
  const plan = new Plan(params);

  return {
    type: PLAN_DELETE,
    payload: plan.delete(),
    id: plan.id,
  };
};

// List Reducer
export const plansReducer = makeActionCollectionReducer('PLANS');

// Details Reducer
let defaultInitialState = {
  loading: true,
  entity: {
    item: {},
  },
  error: null,
};
export const planReducer = makeEntityReducer(
  PLAN_FETCH,
  {},
  defaultInitialState
);
