import { addPrefixToObjectKeys } from 'common/utils/rzp-utils';
import { BUSINESS_TYPE_OPTIONS } from './AccountActivationFormMap';
import {
  trackL1FormSuccess,
  trackL1FormError,
} from 'merchant/containers/Activation/ga_new';
import BingDataObj from 'common/utils/bingDataObj';
import { isPresent } from 'common/utils/rzp-utils';

import {
  trackhubsContactUpdate,
  fireAnalyticsEvents,
  trackTaboola,
} from 'common/utils/googleAnalytics';

function L1FormSuccess(props) {
  trackL1FormSuccess(props.activation_flow);
  props.tracking.trackEvent(window.rzpQ.onbr().initiated('act.submit_form')); // updating contact propteries of hubspot contact
  updateHubSpotContactsProperties(
    {
      ...data,
      activation_flow: props.activation_flow,
      completed: true,
    },
    {},
    'l1_'
  );

  trackTaboola('l1_activation');

  if (props.business_type == 11) {
    const { poi_verification_status } = props;
    if (poi_verification_status == 'verified') {
      props.showPANStatusModal();
    }
  } else {
    const {
      isWhitelistFlow,
      isBlacklistFlow,
      isGraylistFlow,
    } = props.instantActivation;
    if (isWhitelistFlow) {
      props.showInstantActivationSuccessModal();
      fireAnalyticsEvents({ fbData: 'activation_complete_success' });
    } else if (isGraylistFlow) {
      props.showKYCDetailsModal();
    }
  }

  let data = new BingDataObj('activationform', 'complete', 'success', 1);
  fireAnalyticsEvents({
    bingData: data,
    liData: 987404,
    twiData: 'o1ua0',
  }); //fb = false, bing, linkedin, twitter
}

function L1FormError() {
  trackL1FormError();

  let dataError = new BingDataObj('activationform', 'complete', 'error', 1);
  fireAnalyticsEvents({
    fbData: 'activation_complete_error',
    bingData: dataError,
    liData: 987412,
    twiData: 'o1ua2',
  });
}

function updateHubSpotContactsProperties(data, extra, prefix) {
  const keyPrefix = !!prefix ? 'l2_' : prefix;
  const hbsData = addPrefixToObjectKeys(keyPrefix, data);

  const trackData = {
    ...hbsData,
    ...extra,
  };

  if (data.business_type) {
    trackData.l2_business_type = (
      BUSINESS_TYPE_OPTIONS.find(e => e.name == data.business_type) || {}
    ).label;
  }

  if (data.promoter_pan) {
    trackData.l2_promoter_pan = !!trackData.l2_promoter_pan;
  }

  if (data.gstin) {
    trackData.l2_gstin = !!trackData.l2_gstin;
  }

  trackhubsContactUpdate(trackData);
}

const NOT_YET_REGISTERED = 11; // 'Unregistered Businesses
const INDIVIDUAL = 2; // Legacy Type, Now combined under Unregistered Type
const PROPRIETORSHIP = 1;
const NGO = 7; // 'NGO'
const TRUST = 9; // 'Trust'
const SOCIETY = 10; // 'Society'
const LLP = 6; // 'LLP'
const ORG_BusinessTypes = [NGO, TRUST, SOCIETY];
const UNREGISTERED_TYPES = {
  11: true,
  2: true,
};

function differentAddress(activation) {
  return activation.state.same_address === '0';
}

function isUnregisteredBusiness(activation) {
  const currentBusinessType =
    activation.state.dirty['business_type'] ||
    activation.props.data['business_type'];
  return UNREGISTERED_TYPES[Number(currentBusinessType)];
}

function excludeFor_Indiv(activation) {
  return !isUnregisteredBusiness(activation);
}

/* Include for Individual/Not Yet Registered */
function _showForIndiv(activation) {
  return isUnregisteredBusiness(activation);
}

function isL1Submitted(activation) {
  return activation.props.user.instantActivation.isL1Submitted;
}

function isL1Completed(activation) {
  const { user } = activation.props;
  if (user.isSubmitted) {
    return true; // Always return true if user.submitted true -> display all fields and tabs -> handles L2 submission without submitting L1 eg. Batch and EPOS
  }
  return isL1Submitted(activation) && !user.instantActivation.isBlacklistFlow;
}

function excludeFor_CompanyPan(activation) {
  const currentBusinessType =
    activation.state.dirty.business_type || activation.props.data.business_type;
  return (
    [INDIVIDUAL, NOT_YET_REGISTERED, PROPRIETORSHIP].indexOf(
      Number(currentBusinessType)
    ) === -1
  );
}

function requiredForNGO(activation) {
  const selectedBusinessType =
    activation.state.dirty.business_type || activation.props.data.business_type;

  return selectedBusinessType == NGO;
}

function showForOrgs(activation) {
  const selectedBusinessType =
    activation.state.dirty.business_type || activation.props.data.business_type;

  return (
    selectedBusinessType &&
    ORG_BusinessTypes.indexOf(Number(selectedBusinessType)) !== -1
  );
}

function isActivatedUnreg(activation) {
  return isUnregisteredBusiness(activation) && activation.props.user.activated;
}

function checkValidityFromAPI(data, key, errValue, errorMsg) {
  return data && data[key] === errValue ? errorMsg : '';
}

function getPANDescription(data) {
  const { poi_verification_status } = data;
  const description =
    'We verify the details with the central PAN database. Please ensure you enter the correct details';
  return poi_verification_status == 'incorrect_details' ||
    poi_verification_status == 'not_matched'
    ? ''
    : description;
}

function getBeneficiaryInfo() {
  const currentBusinessType =
    this.state.dirty.business_type || this.props.data.business_type;
  if (isUnregisteredBusiness(this)) {
    return 'Please ensure that the spelling is the same as your bank account';
  } else {
    let text = 'Company';

    if (currentBusinessType == LLP) {
      text = 'Individual';
    }

    return `The beneficiary name should be same as ${text} name`;
  }
}

function hasSelectedBlacklistedCategory(activation) {
  const { props, state } = activation;
  const categories = props.categories;
  if (isPresent(categories)) {
    const selectedCategory =
      state.dirty.business_category || props.data.business_category;
    const subcategories =
      selectedCategory && categories[selectedCategory]['subcategories'];
    if (isPresent(subcategories)) {
      const selectedSubcategory =
        state.dirty.business_subcategory || props.data.business_subcategory;
      return (
        subcategories[selectedSubcategory] &&
        (isUnregisteredBusiness(activation)
          ? subcategories[selectedSubcategory][
              'non_registered_activation_flow'
            ] === 'blacklist'
          : subcategories[selectedSubcategory]['activation_flow'] ===
            'blacklist')
      );
    }
  }
  return false;
}

export {
  L1FormSuccess,
  L1FormError,
  updateHubSpotContactsProperties,
  differentAddress,
  isUnregisteredBusiness,
  excludeFor_Indiv,
  UNREGISTERED_TYPES,
  _showForIndiv,
  isL1Submitted,
  isL1Completed,
  excludeFor_CompanyPan,
  requiredForNGO,
  showForOrgs,
  isActivatedUnreg,
  checkValidityFromAPI,
  getPANDescription,
  getBeneficiaryInfo,
  hasSelectedBlacklistedCategory,
};
