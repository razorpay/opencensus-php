const trackGAEvents = (data) => {
  window.rzpAnalytics?.({
    eventCategory: 'LOS | Promoter Info',
    ...data,
  });
};

export const trackGettingLosStarted = (MID) => {
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
  trackGAEvents({
    eventAction: `Getting Started | Cicked on 'Continue' CTA`,
    eventLabel: `Getting started | Complete | ${MID}`,
  });
};

export const trackBusinessFormTab = (MID) => {
  trackGAEvents({
    eventAction: `Landed on Business Details tab`,
    eventLabel: `Business Details | Begin | ${MID}`,
  });
};

export const trackBusinessFormContinueCta = (MID) => {
  trackGAEvents({
    eventAction: `Business Details | Clicked on 'Confirm Business Details' CTA`,
    eventLabel: `Business Details | Complete | ${MID}`,
  });
};

export const trackPersonalFormTab = (MID) => {
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
