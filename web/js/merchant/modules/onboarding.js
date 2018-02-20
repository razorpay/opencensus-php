import ajax from 'merchant/utils/ajax';
import { set } from 'rzp/utils/immutable';
import { merchantFetch } from 'rzp/utils/ajax';

const FEATURE_ONBOARDING_SAVE = 'FEATURE_ONBOARDING_SAVE';
const FEATURE_ONBOARDING_FETCH_RESPONSES = 'FEATURE_ONBOARDING_FETCH_RESPONSES';

// Save onboarding questions
export const saveOnboarding = (feature, fields, file, fileName) => {
  let formData = new FormData();

  if (file) {
    formData.append(fileName, file);
  }

  for (let key in fields) {
    if (fields.hasOwnProperty(key)) {
      formData.append(key, fields[key]);
    }
  }

  return {
    type: FEATURE_ONBOARDING_SAVE,
    payload: merchantFetch({
      url: `feature/onboarding/${feature}`,
      data: formData,
      mode: 'live',
      method: 'post',
    }),
  };
};

// Get responses
export const getOnboardingResponse = feature => {
  return () => merchantFetch({
    url: `feature/onboarding/${feature}/responses`,
    mode: 'live'
  });
};
