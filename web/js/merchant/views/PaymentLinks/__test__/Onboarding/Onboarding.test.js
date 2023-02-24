import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { App, onboarding } from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/Onboarding';
import { render, screen, userEvent } from 'test-utils';
import { LANDING_PAGE_DESC } from 'merchant/views/PaymentLinks/OnBoarding';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';

describe('Payment link Onboarding Screen', () => {
  /*
   * @param {*} props = {}
   * @return <New /> component file
   */
  beforeAll(() => {
    window.rzp_user = {};

    window.rzpQ = {
      component: jest.fn(),
      paymentLinks: () => ({
        interaction: jest.fn(),
      }),
      productOnboarding: () => ({
        success: jest.fn(),
        initiated: jest.fn(),
      }),
    };
  });

  const renderApp = (props = {}, initialState) => {
    render(<App {...props} />, {
      initialState: initialState || {
        session: {
          user: { isPaymentLinksEnabled: true },
          org: {
            custom_code: 'rzp',
            business_name: 'Razorpay',
          },
        },
        onboarding,
      },
    });
  };
  test('should render Onboarding component without errors', () => {
    expect(renderApp).not.toThrowError();
  });

  test('should load onboarding initial screen', () => {
    renderApp();
    expect(screen.getByText(LANDING_PAGE_DESC[ORG_CUSTOM_CODE_MAP.RAZORPAY])).toBeInTheDocument();
  });

  test('should load initial components "Skip & Read More" ', () => {
    renderApp();
    expect(screen.getByText(/Read More/i)).toBeInTheDocument();
    expect(screen.getByText(/Skip And Get Started/i)).toBeInTheDocument();
  });

  test('should load feature page of pl onboarding module', async () => {
    const closeOnboardingMock = jest.fn();
    const props = {
      closeOnboarding: closeOnboardingMock,
    };
    renderApp(props);
    const readMoreCTA = screen.getByRole('button', {
      name: /Read More/,
    });
    expect(readMoreCTA).toBeInTheDocument();
    await userEvent.click(readMoreCTA);
    expect(screen.getByText(/What makes Payment Links great/i)).toBeInTheDocument();
    expect(
      screen.getByText(
        "Don't have an app or website for selling? Now let your customers pay online with payment links",
      ),
    ).toBeInTheDocument();
    expect(screen.getByText(/Alternative Payment Option/i)).toBeInTheDocument();
    expect(
      screen.getByText(
        /Payment Links can be an easy substitute for cash-on-delivery and point-of-sale payment methods in your business./i,
      ),
    ).toBeInTheDocument();
    expect(screen.getByText(/Partial Payments/i)).toBeInTheDocument();
    expect(
      screen.getByText(
        /Provide your customers with the flexibility to make payments in parts against large orders instead of making the entire payment at once./i,
      ),
    ).toBeInTheDocument();
    const goBackCTA = screen.getByText('Back');
    const getStartedCTA = screen.getByText('Get Started');
    const skipAndGetStartedCTA = screen.getByText('Skip And Get Started');
    expect(goBackCTA).toBeInTheDocument();
    expect(getStartedCTA).toBeInTheDocument();
    expect(skipAndGetStartedCTA).toBeInTheDocument();
    await userEvent.click(skipAndGetStartedCTA);
    expect(closeOnboardingMock).toHaveBeenCalled();
  });

  test('should load org business name ', () => {
    renderApp(null, {
      session: {
        user: { isPaymentLinksEnabled: true },
        org: {
          custom_code: 'curlec',
          business_name: 'Curlec',
        },
      },
      onboarding,
    });

    expect(screen.getByText(/Curlec/i)).toBeInTheDocument();
  });

  test('should load curlec onboarding initial screen', () => {
    renderApp(null, {
      session: {
        user: { isPaymentLinksEnabled: true },
        org: {
          custom_code: 'curlec',
          business_name: 'Curlec',
        },
      },
      onboarding,
    });

    expect(screen.getByText(LANDING_PAGE_DESC[ORG_CUSTOM_CODE_MAP.CURLEC])).toBeInTheDocument();
  });
});
