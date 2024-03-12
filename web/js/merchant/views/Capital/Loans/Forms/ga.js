import { analyticsTrack } from 'common/utils/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';

export const EVENT_TYPES = {
  RENDERED: 'Rendered',
  CLICKED: 'clicked',
};

/**
 * @param {{objectName, actionName, screen, properties}} obj - Object to be sent to analytics service
 */
const trackSegmentEvent = ({
  objectName,
  actionName,
  screen = 'Cash Advance Onboarding',
  properties = {},
}) => {
  try {
    analyticsTrack({
      objectName,
      actionName,
      screen,
      properties: {
        ...getCommonSegmentProperties(),
        ...properties,
      },
    });
  } catch (e) {
    // empty catch block
  }
};

const trackGAEvents = (data) => {
  window.rzpAnalytics?.({
    eventCategory: 'LOS | Promoter Info',
    ...data,
  });
};

export const trackGettingLosStarted = (MID) => {
  trackSegmentEvent({
    objectName: 'Getting Started Tab',
    actionName: EVENT_TYPES.RENDERED,
  });
  trackGAEvents({
    eventAction: `Landed on Getting Started Tab`,
    eventLabel: `Getting Started | Begin | ${MID}`,
  });
};

export const trackDesiredLoanAmount = (MID, amount) => {
  trackGAEvents({
    eventAction: `Getting Started | Picked a value from the list | Desired Amount`,
    eventLabel: `Intention | Desired Amount | ${amount} | ${MID}`,
  });
};

export const trackLoanReason = (MID, reasons) => {
  trackGAEvents({
    eventAction: `Getting Started | Picked a values from the list | Reason`,
    eventLabel: `Intention | Desired Amount | ${reasons} | ${MID}`,
  });
};

export const trackIntentFormContinueCta = (MID) => {
  trackSegmentEvent({
    objectName: 'Getting Started Tab Continue CTA',
    actionName: EVENT_TYPES.CLICKED,
  });
  trackGAEvents({
    eventAction: `Getting Started | Cicked on 'Continue' CTA`,
    eventLabel: `Getting started | Complete | ${MID}`,
  });
};

export const trackBusinessFormTab = (MID) => {
  trackSegmentEvent({
    objectName: 'Business Details tab',
    actionName: EVENT_TYPES.RENDERED,
  });
  trackGAEvents({
    eventAction: `Landed on Business Details tab`,
    eventLabel: `Business Details | Begin | ${MID}`,
  });
};

export const trackBusinessFormContinueCta = (MID) => {
  trackSegmentEvent({
    objectName: "Business details 'Confirm business details' CTA",
    actionName: EVENT_TYPES.CLICKED,
  });
  trackGAEvents({
    eventAction: `Business Details | Clicked on 'Confirm Business Details' CTA`,
    eventLabel: `Business Details | Complete | ${MID}`,
  });
};

export const trackPersonalFormTab = (MID) => {
  trackSegmentEvent({
    objectName: 'Personal Details tab',
    actionName: EVENT_TYPES.RENDERED,
  });
  trackGAEvents({
    eventAction: `Landed on Personal Details tab`,
    eventLabel: `Personal Details | Begin | ${MID}`,
  });
};

export const trackMajorStackholderFill = (MID, majorityStakeholder, source) => {
  trackGAEvents({
    eventAction: `Clicked on ${majorityStakeholder} for business ownership`,
    eventLabel: `Personal Details | ${source} | Ownership - ${majorityStakeholder} | ${MID}`,
  });
};

export const trackCheckEligibilityCta = (MID, majorityStakeholder, source, loanId) => {
  trackSegmentEvent({
    objectName: "Personal detail 'check your eligibility' CTA",
    actionName: EVENT_TYPES.CLICKED,
    properties: {
      majorityStakeholder,
      source,
      loanId,
    },
  });
  trackGAEvents({
    eventAction: `Personal Details | Clicked on 'Check Eligibility'`,
    eventLabel: `Personal Details | ${source} | Ownership - ${majorityStakeholder} | Complete |  ${MID} | Loan ID - ${loanId}`,
  });
};

export const trackPromoterDetailsBeforeOtp = (MID) => {
  trackGAEvents({
    eventAction: `Clicked on Promoter Info navigation`,
    eventLabel: `Promoter Info | Before OTP success | ${MID}`,
  });
};

export const trackPromoterDetailsAfterOtp = (MID) => {
  trackGAEvents({
    eventAction: `Clicked on Promoter Info navigation`,
    eventLabel: `Promoter Info | After OTP success | ${MID}`,
  });
};

export const trackTabChange = (MID, tabName, source) => {
  trackGAEvents({
    eventAction: `Clicked on ${tabName}`,
    eventLabel: `${source} | Tab - ${tabName} | ${MID}`,
  });
};

export const trackTermsOrPolicy = (MID, name) => {
  trackGAEvents({
    eventAction: `Application | ${name}`,
    eventLabel: `Check Loan Eligibility | Promoter Details ${name} | ${MID}`,
  });
};

export const trackCashAdvanceV2Rendered = () => {
  trackSegmentEvent({
    objectName: 'Cash Advance Homepage New Rendered V2',
    actionName: EVENT_TYPES.RENDERED,
  });
};

export const trackApplyNowCTACashAdvanceV2 = () => {
  trackSegmentEvent({
    objectName: 'Apply Now V2',
    actionName: EVENT_TYPES.CLICKED,
  });
};
