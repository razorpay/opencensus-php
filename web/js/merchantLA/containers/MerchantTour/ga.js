import { track } from 'merchant/containers/Home/ga';

export const trackSkipTour = step => {
  track({
    eventAction: 'Skip - Tour',
    eventLabel: `Skipped at step - ${step}`,
  });
};

export const trackFinishTour = () => {
  track({
    eventAction: 'Finish - Tour',
  });
};
