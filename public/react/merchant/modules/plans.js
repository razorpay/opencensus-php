import Plan from 'merchant/models/Plan';
import { makeActionCollectionReducer, fetchAll } from 'rzp/modules/collection';
import { makeEntityReducer, updateEntity } from 'rzp/modules/entity';
import { set } from 'rzp/utils/immutable';

export const PLANS_FETCH = 'PLANS_FETCH';
export const PLAN_CREATE = 'PLAN_CREATE';
export const PLAN_EDIT = 'PLAN_EDIT';
export const PLAN_DELETE = 'PLAN_DELETE';
export const PLAN_FETCH = 'PLAN_FETCH';
export const PLAN_FETCH_SUBSCRIPTIONS = 'PLAN_FETCH_SUBSRIPTIONS';

export const fetchPlans = params => fetchAll(params, Plan, 'PLANS');

export const fetchPlan = id => {
  let plan = new Plan();
  return {
    type: PLAN_FETCH,
    payload: plan.fetch(id),
  };
};

export const fetchSubscriptionsByPlanId = plan => {
  return {
    type: PLAN_FETCH_SUBSCRIPTIONS,
    payload: plan.fetchSubscriptions(),
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

const updateSubscriptions = status => (state, action) => {
  switch (status) {
    case 'PENDING': {
      return set(state, 'subscriptions', {
        loading: true,
        items: [],
      });
    }
    case 'SUCCESS': {
      return set(state, 'subscriptions', {
        loading: false,
        items: action.payload.data.items,
      });
    }
    case 'ERROR': {
      return set(state, 'subscriptions', {
        loading: false,
        items: [],
        error: action.payload.errors,
      });
    }
  }
};

// List Reducer
export const plansReducer = makeActionCollectionReducer('PLANS');

// Plan Details Initial State
let planInitialState = {
  subscriptions: {
    loading: false,
    items: [],
  },
};

// Details Reducer
export const planReducer = makeEntityReducer(
  PLAN_FETCH,
  {
    [`${PLAN_FETCH_SUBSCRIPTIONS}::PENDING`]: updateSubscriptions('PENDING'),
    [`${PLAN_FETCH_SUBSCRIPTIONS}::SUCCESS`]: updateSubscriptions('SUCCESS'),
    [`${PLAN_FETCH_SUBSCRIPTIONS}::ERROR`]: updateSubscriptions('ERROR'),
  },
  planInitialState
);
