import { setTrackData } from 'rzp/utils/googleAnalytics';

const track = setTrackData({
  eventCategory: 'Onboarding - Card',
});

export const trackWelcomeCTAClick = () =>
  track({
    eventAction: 'Click - Welcome CTA',
  });

export const trackActivationCardAction = (action, stepNum, desc) =>
  track({
    eventAction: action,
    eventLabel: `Activation Card - Step${stepNum} - ${desc}`,
  });

export const trackGoToActivation = stepNum => {
  return trackActivationCardAction(
    'Go To - Activation form',
    stepNum,
    'Fill Activation Form'
  );
};

export const trackGoToPersonalise = stepNum => {
  return trackActivationCardAction(
    'Go To - Config',
    stepNum,
    'Personalise Account'
  );
};

export const trackSwitchToLive = stepNum => {
  return trackActivationCardAction('Switch - Mode', stepNum, 'Switch to Live');
};

export const trackIntegrationCardAction = (action, stepNum, desc, mode) =>
  track({
    eventAction: action,
    eventLabel: `Integration Card - Step${stepNum} - ${desc}(${mode})`,
  });

export const trackGoToKeyGen = (stepNum, mode) =>
  trackIntegrationCardAction(
    'Go To - API Keys',
    stepNum,
    'Generate Keys',
    mode
  );

export const trackGoToDocumentation = (stepNum, mode) =>
  trackIntegrationCardAction(
    'Go To - Documentation',
    stepNum,
    'Documentation',
    mode
  );

export const trackGoToPayments = (stepNum, mode) =>
  trackIntegrationCardAction(
    'Go To - Payments',
    stepNum,
    'View Transactions',
    mode
  );

export const trackCloseOnboarding = desc =>
  track({
    eventAction: 'Click - Close Onboarding Banner',
    eventLabel: desc,
  });
