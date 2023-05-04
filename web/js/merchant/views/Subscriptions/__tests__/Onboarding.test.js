import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';
import Onboarding, {
  getIsAllowedResetSubscriptionBoarding,
} from 'merchant/views/Subscriptions/OnBoarding';
import analytics from 'merchant/views/Subscriptions/analytics';

jest.mock('merchant/components/OnBoarding', () => ({
  ...jest.requireActual('merchant/components/OnBoarding'),
  __esModule: true,
  getIsAllowedResetBoarding: jest.fn().mockReturnValue(true),
}));

describe('Subscriptions Onboarding Screen', () => {
  let analyticsSpy;

  const renderApp = (props = {}) => {
    render(<Onboarding {...props} />, {
      initialState: {
        session: {
          user: { isSubscriptionsEnabled: true },
          org: { custom_code: 'rzp', business_name: 'Razorpay' },
        },
      },
    });
  };

  beforeAll(() => {
    window.open = jest.fn();
    window.rzp_user = {};

    window.rzpQ = {
      component: jest.fn(),
      interaction: jest.fn(),
      subscription: () => ({
        interaction: jest.fn(),
      }),
      productOnboarding: () => ({
        success: jest.fn(),
        initiated: jest.fn(),
      }),
    };
  });

  beforeEach(() => {
    analyticsSpy = jest.spyOn(analytics, 'track');
    renderApp();
  });

  afterEach(() => {
    analyticsSpy.mockClear();
  });

  test('Should load onboarding initial screen', () => {
    [
      'Collect recurring payments from customers with Razorpay Subscriptions APIs',
      '% Reduction in churn',
      '% Lower collection costs',
      'x increase in CLTV',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
  });

  test('Should load initial components "Skip & Read More" ', () => {
    ['Read More', 'Skip And Get Started'].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
  });

  test('Should load feature page of subscriptions onboarding module', async () => {
    const readMoreCTA = screen.getByRole('button', {
      name: /Read More/i,
    });
    await userEvent.click(readMoreCTA);
    [
      'Subscription Links',
      'Share unique links to onboard your customers on your Subscription plans instantly! Zero coding, Zero integration.',
      'Multi-currency Support',
      'Accept recurring payments from customers in India and abroad via Razorpay Subscriptions. 100 currencies supported!',
      'Multiple Payment methods',
      'Offer your customers a wide variety of payment methods. All payment methods which support recurring payments are compliant with RBI regulations.',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });

    expect(screen.getByText('Back')).toBeInTheDocument();
    const getStartedCTA = screen.getByText('Get Started');
    await userEvent.click(getStartedCTA);
    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('subscription.tutorial.read_more');
    });

    const knowMoreLink = screen.getByText(/know more/i);
    await userEvent.click(knowMoreLink);
    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('subscription.tutorial.know_more');
    });

    const viewAPIDoc = screen.getByText(/view api docs/i);
    await userEvent.click(viewAPIDoc);
    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('subscription.tutorial.view_api_docs');
    });

    const skipAndGetStartedCTA = screen.getByText('Skip And Get Started');
    await userEvent.click(skipAndGetStartedCTA);
    await waitFor(() => {
      expect(analyticsSpy).toHaveBeenCalledWith('subscription.tutorial.get_started');
    });
  });

  test('Show onboardings when plans or subscriptions doesnt exist', () => {
    const plans = {
      loading: false,
      items: [],
    };
    const subscriptions = {
      loading: false,
      items: [],
    };
    expect(getIsAllowedResetSubscriptionBoarding({ plans, subscriptions })).toBe(true);
  });

  test('Dont show onboardings when plans or subscriptions exist', () => {
    const plans = {
      loading: false,
      items: [
        {
          id: 'plan_123',
        },
      ],
    };
    const subscriptions = {
      loading: false,
      items: [
        {
          id: 'subs_123',
        },
      ],
    };
    expect(getIsAllowedResetSubscriptionBoarding({ plans, subscriptions })).toBe(false);
  });
});
