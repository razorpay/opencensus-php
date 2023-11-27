import { set } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';

const FETCH_MERCHANT_WEBSITE_DETAILS = 'FETCH_MERCHANT_WEBSITE_DETAILS';
const UPDATE_BANNER_MODAL_VISIBILITY = 'UPDATE_BANNER_MODAL_VISIBILITY';
const GET_BANNER_MODAL_VISIBILITY = 'GET_BANNER_MODAL_VISIBILITY';
export const GET_USER_ACTIVATION_DETAILS = 'GET_USER_ACTIVATION_DETAILS';
const FETCH_ELIGIBILITY_FOR_POLICY_WIZARD_V2 = 'FETCH_ELIGIBILITY_FOR_POLICY_WIZARD_V2';

export const fetchActivationDetails = () => {
  return {
    type: GET_USER_ACTIVATION_DETAILS,
    payload: merchantFetch({
      url: 'merchant/activation',
    }),
  };
};

export const fetchEligibilityForPolicyWizardV2 = () => {
  return {
    type: FETCH_ELIGIBILITY_FOR_POLICY_WIZARD_V2,
    payload: merchantFetch({
      url: 'pg/onboarding/merchant_get_l2_dynamic_configs',
      mode: 'live',
    }),
  };
};

export const fetchMerchantWebsiteDetails = () => {
  return {
    type: FETCH_MERCHANT_WEBSITE_DETAILS,
    payload: merchantFetch({
      url: 'merchant/website/section',
    }),
  };
};

export const getBannerAndModalVisibility = () => {
  return {
    type: GET_BANNER_MODAL_VISIBILITY,
    payload: merchantFetch({
      url: 'merchants/config/store?namespace=onboarding',
    }),
  };
};

export const updateBannerAndModalVisibility = (requestData) => {
  return {
    type: UPDATE_BANNER_MODAL_VISIBILITY,
    payload: merchantFetch({
      url: 'merchants/config/store?namespace=onboarding',
      data: requestData,
      method: 'POST',
    }),
  };
};

const initialState = {
  websiteSectionDetailsData: {
    loading: false,
    data: {},
    error: false,
  },
  bannerAndModalVisibility: {
    loading: false,
    data: {},
    error: false,
  },
  activationData: {
    loading: false,
    data: {},
    error: false,
  },
  policyWizardV2Data: {
    loading: false,
    isEligible: false,
    isDataLoaded: false,
    error: false,
  },
};

export default function websiteComplianceReducer(state = initialState, action) {
  switch (action.type) {
    case `${FETCH_MERCHANT_WEBSITE_DETAILS}::PENDING`:
      return set(
        state,
        'websiteSectionDetailsData',
        set(state.websiteSectionDetailsData, 'loading', true),
      );

    case `${FETCH_MERCHANT_WEBSITE_DETAILS}::SUCCESS`: {
      return set(state, 'websiteSectionDetailsData', {
        ...state.websiteSectionDetailsData,
        loading: false,
        data: action.payload.data,
      });
    }

    case `${FETCH_MERCHANT_WEBSITE_DETAILS}::ERROR`: {
      return set(state, 'websiteSectionDetailsData', {
        ...state.websiteSectionDetailsData,
        loading: false,
        data: {},
        error: action.payload.errors.join(''),
      });
    }

    case `${FETCH_ELIGIBILITY_FOR_POLICY_WIZARD_V2}::PENDING`:
      return set(state, 'policyWizardV2Data', {
        ...state.policyWizardV2Data,
        loading: true,
      });

    case `${FETCH_ELIGIBILITY_FOR_POLICY_WIZARD_V2}::SUCCESS`: {
      return set(state, 'policyWizardV2Data', {
        ...state.policyWizardV2Data,
        loading: false,
        isEligible: !!action?.payload?.data?.is_policy_wizard_v2_eligible,
        isDataLoaded: true,
      });
    }

    case `${FETCH_ELIGIBILITY_FOR_POLICY_WIZARD_V2}::ERROR`: {
      return set(state, 'policyWizardV2Data', {
        ...state.policyWizardV2Data,
        loading: false,
        isEligible: false,
        error: action.payload.errors.join(''),
      });
    }

    case `${GET_USER_ACTIVATION_DETAILS}::PENDING`:
      return set(state, 'activationData', set(state.activationData, 'loading', true));

    case `${GET_USER_ACTIVATION_DETAILS}::SUCCESS`: {
      return set(state, 'activationData', {
        ...state.activationData,
        loading: false,
        data: action.payload.data,
      });
    }

    case `${GET_USER_ACTIVATION_DETAILS}::ERROR`: {
      return set(state, 'activationData', {
        ...state.activationData,
        loading: false,
        data: {},
        error: action.payload.errors.join(''),
      });
    }

    case `${GET_BANNER_MODAL_VISIBILITY}::PENDING`:
      return set(
        state,
        'bannerAndModalVisibility',
        set(state.bannerAndModalVisibility, 'loading', true),
      );

    case `${GET_BANNER_MODAL_VISIBILITY}::SUCCESS`:
      return set(state, 'bannerAndModalVisibility', {
        ...state.bannerAndModalVisibility,
        loading: false,
        data: action.payload.data,
      });

    case `${GET_BANNER_MODAL_VISIBILITY}::ERROR`:
      return set(state, 'bannerAndModalVisibility', {
        ...state.bannerAndModalVisibility,
        loading: false,
        data: {},
        error: action.payload.data,
      });

    default:
      return state;
  }
}
