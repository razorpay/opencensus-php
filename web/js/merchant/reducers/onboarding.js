import ajax, { merchantFetch } from 'merchant/utils/ajax';
import { set } from 'common/utils/immutable';
import { getOnBoardingDataFromLocalState } from 'merchant/components/OnBoarding';

const FEATURE_ONBOARDING_SAVE = 'FEATURE_ONBOARDING_SAVE';
const QUICK_GUIDE = 'QUICK_GUIDE';

export const handleProductQuickGuide = data => {
  return {
    type: QUICK_GUIDE,
    payload: {
      feature: data.feature,
      isEnabled: data.isEnabled,
      showOnboarding: data.showOnboarding,
      isQuickGuideOpen: data.isQuickGuideOpen,
      isTour: data.isTour,
      lastElementId: data.lastElementId,
    },
  };
};

export const getCurrentProductOnBoardingDetails = (state, feature) => {
  const localState = getOnBoardingDataFromLocalState(feature);

  return (
    state.onboarding.products[feature] || {
      feature,
      showOnboarding: false,
      isQuickGuideOpen: false,
      isTour: false,
      isEnabled: false,
      ...localState
    }
  );
};

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

let initialState = {
  products: {},
};

export default function(state = initialState, action) {
  switch (action.type) {
    case QUICK_GUIDE: {
      return set(state, 'products', {
        ...state.products,
        [action.payload.feature]: action.payload,
      });
    }

    default:
      return state;
  }
}
