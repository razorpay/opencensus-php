import analyticsService from '@razorpay/commander-services/analytics';

const trackEvent = (obj) => {
  try {
    analyticsService.track({
      ...obj,
      properties: {
        ...obj.properties,
      },
    });
  } catch (e) {
    // handle error
  }
};

export const trackFirstScreenRender = (lender = 'Indifi') =>
  trackEvent({
    objectName: 'Working Capital Loans',
    actionName: 'Rendered',
    screen: 'Working Capital Loans || Offer Page',
    properties: {
      'Loan Type': 'Non-FLDG Loans',
      Lender: lender,
    },
  });

export const trackClickHandler = () =>
  trackEvent({
    objectName: 'Accept & Continue',
    actionName: 'Clicked',
    screen: 'Working Capital Loans || Offer Page',
    properties: {},
  });
