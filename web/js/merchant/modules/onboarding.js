import ajax from 'merchant/utils/ajax';
import { set } from 'rzp/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';

const FEATURE_ONBOARDING_SAVE = 'FEATURE_ONBOARDING_SAVE';
const FEATURE_ONBOARDING_FETCH_RESPONSES = 'FEATURE_ONBOARDING_FETCH_RESPONSES';

// Save onboarding questions
export const saveOnboarding = (
  feature,
  fields,
  file,
  fileName,
  type = 'product'
) => {
  let formData = new FormData();

  if (file) {
    formData.append(`submissions[${fileName}]`, file);
  }
  for (let key in fields) {
    if (fields.hasOwnProperty(key)) {
      formData.append(`submissions[${key}]`, fields[key]);
    }
  }

  formData.append('name', feature);
  formData.append('type', type);

  return {
    type: FEATURE_ONBOARDING_SAVE,
    payload: merchantFetch({
      url: `merchant/requests`,
      data: formData,
      mode: 'live',
      method: 'post',
    }),
  };
};

// Get responses
export const getOnboardingResponse = (feature, type = 'product') => {
  return () =>
    merchantFetch({
      url: `merchant/requests/${type}/${feature} `,
      mode: 'live',
    });
};
