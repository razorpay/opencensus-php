import ajax from 'merchant/utils/ajax';
import { set } from 'rzp/utils/immutable';

const FEATURE_ONBOARDING_SAVE = 'FEATURE_ONBOARDING_SAVE';
const FEATURE_ONBOARDING_FETCH_RESPONSES = 'FEATURE_ONBOARDING_FETCH_RESPONSES';

// Save onboarding questions
export const saveOnboarding = (feature, fields, file, fileName) => {
  let formData = new FormData();

  if (file) {
    formData.append('file', file);
    formData.append('file_name', fileName);
  }

  formData.append('route_name', 'feature_onboarding_create');
  formData.append('mode', 'live');
  formData.append('url_params[{feature}]', feature);

  for (let key in fields) {
    if (fields.hasOwnProperty(key)) {
      formData.append(`body[${key}]`, fields[key]);
    }
  }
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
