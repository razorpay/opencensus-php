import { isExperimentEnabled } from 'common/splitz/utils';

export const fireCustomEvent = ({ event = 'custom-event', data = {}, elementId = '' }) => {
  if (document && window && window.CustomEvent) {
    const customEv = new CustomEvent(event, { detail: data });
    if (elementId) {
      document.getElementById(elementId).dispatchEvent(customEv);
    } else {
      document.dispatchEvent(customEv);
    }
  }
};

//Todo: remove post FTX
export const isMerchantPosActivated = (splitz) => {
  const { abExperiments } = splitz || {
    abExperiments: { is_merchant_pos_for_ftx: undefined },
  };

  if (!abExperiments?.is_merchant_pos_for_ftx) return false;

  return isExperimentEnabled(abExperiments.is_merchant_pos_for_ftx);
};

export const getPosActivationStatus = (user, splitz) => {
  if (isMerchantPosActivated(splitz)) {
    return 'activated';
  }
  return user?.pos_activation_status;
};

export const isHelpWidgetDisabled = (splitz) => {
  const { abExperiments } = splitz || {
    abExperiments: { is_help_widget_disabled: undefined },
  };

  return isExperimentEnabled(abExperiments.is_help_widget_disabled);
};
