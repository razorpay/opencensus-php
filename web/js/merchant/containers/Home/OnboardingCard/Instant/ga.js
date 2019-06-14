import { setTrackData, fireAnalyticsEvents } from 'rzp/utils/googleAnalytics';
import BingDataObj from '../../../../../rzp/utils/bingDataObj';

const track = setTrackData({
  eventCategory: 'Dashboard - Instant Activations Onboarding',
});

export const trackClose = () => {
  track({
    eventAction: 'Click - Close Instant activations onboarding banner',
  });
};

const trackTransactionsClick = function() {
  track({
    eventAction: `Click - View Transactions ${this.name}`,
  });
};

export const trackTestModeCard = {
  name: 'Test Mode Card',
  switchToTest() {
    track({
      eventAction: 'Click - Switch To Test Mode',
    });
  },
  viewTransactions() {
    trackTransactionsClick.apply(this);
  },
  generateTestKeys() {
    track({
      eventAction: `Click - Generate test keys`,
    });
  },
  viewTestProducts() {
    track({
      eventAction: `Click - View test products`,
    });
  },
};

export const trackLiveModeCard = {
  name: 'Live Mode Card',
  switchToLive() {
    track({
      eventAction: 'Click - Take me to Live Mode',
    });
  },
  fillActivationForm() {
    track({
      eventAction: 'Click - Fill Activation Form Link',
    });
  },
  fillKYCForm() {
    track({
      eventAction: 'Click - Fill KYC Form Link',
    });
  },
  viewTransactions() {
    trackTransactionsClick.apply(this);
  },
  howDoIAcceptPayments() {
    track({
      eventAction: 'Click - How do i accept payments - live card',
    });
  },
};

export const trackActivationCard = {
  activateAccount() {
    track({
      eventAction: 'Click - Activate Account',
    });
  },
  fillKyc() {
    track({
      eventAction: 'Click - Fill KYC',
    });
  },
  refillActivationForm() {
    track({
      eventAction: 'Click - Blacklist - Refill KYC',
    });
  },
};

export const trackDotClick = dotDetail => {
  track({
    eventAction: `Click - Dot ${dotDetail}`,
  });
};

export const trackTransactionsHelper = {
  trackIntegration() {
    track({
      eventAction: 'Click - Start integrating',
    });
  },
  trackViewProducts() {
    track({
      eventAction: 'Click - View products',
    });
  },
  trackClose() {
    track({
      eventAction: 'Click - Close how do i integrate modal',
    });
  },
};

export const trackProductsModal = {
  trackProductClick(productName) {
    track({
      eventAction: `Click - Product ${productName}`,
    });
  },
  trackProductsBack() {
    track({
      eventAction: `Click - Back button products modal`,
    });
  },
  trackClose() {
    track({
      eventAction: 'Click - Close products modal',
    });
  },
};

export const trackPersonaliseBanner = () => {
  return track({
    eventAction: 'Click - Personalize Account',
  });
};
