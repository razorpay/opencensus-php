import ajax from 'merchant/utils/ajax';
import { set } from 'rzp/utils/immutable';

const FEATURE_ONBOARDING_SAVE = 'FEATURE_ONBOARDING_SAVE';

// Save onboarding questions
export const saveOnboarding = data => {
  let body = {
    route_name: 'feature_onboarding_create',
    body: data,
  };

  return {
    type: FEATURE_ONBOARDING_SAVE,
    payload: ajax({
      url: '/user/generic',
      method: 'POST',
      appendModeInURL: false,
      data: body,
    }),
  };
};

// Initial state
let initialState = {
  onboarding: {},
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${FEATURE_ONBOARDING_SAVE}::SUCCESS`:
      return set(state, 'onboarding', action.payload.data);

    default:
      return state;
  }
}
