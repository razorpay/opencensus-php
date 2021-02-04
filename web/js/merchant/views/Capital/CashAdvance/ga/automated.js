import { setTrackData } from 'common/utils/googleAnalytics';

export const EVENT_CATEGORY_CASH_ADVANCE_AUTOMATED = 'Cash Advance - Automated';

const EVENTS = {
  clickSelectDisableReason: {},
  clickConfirmAndCloseDisableAutomatedModal: {
    eventAction: 'Click Confirm & Close button',
  },
  clickCloseDisableAutomatedModal: {
    eventAction: 'Click close modal icon',
    eventLabel: 'Reason Modal | Click icon',
  },
  changeBriefText: {
    eventAction: 'Active on Write a brief',
    eventLabel: 'Reason Modal | Write a brief',
  },
  hoverAutomatedText: {
    eventAction: 'Hover Automated label',
    eventLabel: 'Tooltip | Open',
  },
  clickDisableAutomatedWithdrawal: {
    eventAction: 'Click Disable Automated Withdrawals link',
    eventLabel: 'Tooltip | Click link - Disable Automated Withdrawal',
  },
  clickNoDontDisableAutomatedModal: {
    eventAction: "Click No, don't button",
    eventLabel: 'Disable Modal | Click No button',
  },
  clickYesDisabledAutomatedModal: {
    eventAction: "Click 'Yes, Disable' button",
    eventLabel: 'Disable Modal | Click Yes button',
  },
  clickAutomatedWithdrawalEnable: {
    eventAction: 'Click Enable Now link(TP1)',
    eventLabel: 'Click link | Enable Now(TP1)',
  },
  clickAutomatedWithdrawalEnableForResults: {
    eventAction: 'Click Enable Now link(TP2)',
    eventLabel: 'Click link | Enable Now(TP2)',
  },
  clickDoneInSuccessfullyEnabled: {
    eventAction: 'Click Done button',
    eventLabel: 'Success Modal | Click button - Done',
  },
  clickContactSupportInSuccessfullyEnabled: {
    eventAction: 'Click Contact Support Link',
    eventLabel: 'Success Modal | Click link - Contact Support',
  },
  clickCloseModalInSuccessfullyEnabled: {
    eventAction: 'Click close modal icon',
    eventLabel: 'Success Modal | Click close icon',
  },
  clickEnableAutomatedWithdrawal: {
    eventAction: 'Click Enable Automated Withdrawals button',
    eventLabel: 'Enable Modal | Click button',
  },
  clickCloseInEnableAutomatedModal: {
    eventAction: 'Click close modal icon',
    eventLabel: 'Enable Modal | Click close icon',
  },
  hoverInfoIconInEnableAutomatedModal: {
    eventAction: 'Hover info Icon',
    eventLabel: 'Enable Modal | Hover info icon',
  },
  openEnableAutomatedWithdrawModal: {
    eventAction: 'Modal Opens',
    eventLabel: 'Enable Modal | Open confirm',
  },
  openSuccessfullyEnabledAutomatedWithdrawModal: {
    eventAction: 'Success Modal',
    eventLabel: 'Success Modal | Enable success',
  },
};

const trackAutomatedCA = {};

Object.keys(EVENTS).forEach((key) => {
  trackAutomatedCA[key] = (...params) => {
    setTrackData({
      eventCategory: EVENT_CATEGORY_CASH_ADVANCE_AUTOMATED,
      ...EVENTS[key],
      ...params,
    })();
  };
});

export default trackAutomatedCA;
