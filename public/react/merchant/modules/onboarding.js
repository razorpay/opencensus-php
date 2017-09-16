import ajax from 'merchant/utils/ajax';
import { set } from 'rzp/utils/immutable';

const FEATURE_ONBOARDING_SAVE = 'FEATURE_ONBOARDING_SAVE';
const FEATURE_ONBOARDING_FETCH_RESPONSES = 'FEATURE_ONBOARDING_FETCH_RESPONSES';

// Save onboarding questions
export const saveOnboarding = data => {
  let body = {
    route_name: 'feature_onboarding_create',
    mode: 'live',
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

// Get responses
export const getOnboardingResponse = feature => {
  let body = {
    route_name: 'feature_onboarding_fetch_responses',
    mode: 'live',
    url_params: {
      '{feature}': feature,
    },
  };

  return () => {
    return ajax({
      url: '/user/generic',
      method: 'GET',
      appendModeInURL: false,
      data: body,
    });
  };
};
