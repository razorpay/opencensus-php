import { addPrefixToObjectKeys } from 'common/util';
import { BUSINESS_TYPE_OPTIONS } from './AccountActivationFormMap';
import {
  trackL1FormSuccess,
  trackL1FormError,
} from 'merchant/containers/Activation/ga_new';
import BingDataObj from 'rzp/utils/bingDataObj';

import {
  trackhubsContactUpdate,
  fireAnalyticsEvents,
  trackTaboola,
} from 'rzp/utils/googleAnalytics';

function L1FormSuccess(props) {
  trackL1FormSuccess(props.activation_flow);
  props.tracking.trackEvent(window.rzpQ.onbr().initiated('act.submit_form'));
  // updating contact propteries of hubspot contact
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
  }); //fb = false, bing, linkedin, twitter
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

export { L1FormSuccess, L1FormError, updateHubSpotContactsProperties };
