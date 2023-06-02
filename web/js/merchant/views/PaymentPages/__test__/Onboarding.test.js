import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { App, onboarding } from 'merchant/views/PaymentPages/__test__/mocks/fixtures/Onboarding';
import { render, screen, userEvent } from 'test-utils';
import { LANDING_PAGE_DESC } from 'merchant/views/PaymentPages/OnBoarding';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';

describe('Payment pages Onboarding Screen', () => {
  /*
   * @param {*} props = {}
   * @return <New /> component file
   */
  beforeAll(() => {
    window.rzp_user = {};

    window.rzpQ = {
      component: jest.fn(),
      paymentPages: () => ({
        interaction: jest.fn(),
      }),
      productOnboarding: () => ({
        success: jest.fn(),
        initiated: jest.fn(),
      }),
    };
  });

  const renderApp = (props = {}, initialState = null) => {
    render(<App {...props} />, {
      initialState: initialState || {
        session: {
          user: { isPaymentPagesEnabled: true },
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

  test('should load feature page of pp onboarding module', async () => {
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
    expect(screen.getByText(/No Coding Required/i)).toBeInTheDocument();
    expect(
      screen.getByText(
        'Your business can go online with zero integration and tech efforts. We build and operate for you.',
      ),
    ).toBeInTheDocument();

    expect(screen.getByText(/Custom Branded Page/i)).toBeInTheDocument();
    expect(
      screen.getByText(
        /Customize the look and feel of your payment pages to reflect your brand colours, for seamless customer experience./i,
      ),
    ).toBeInTheDocument();

    expect(screen.getAllByText(/Payment Button/i)[0]).toBeInTheDocument();
    expect(
      screen.getByText(
        /Design a customized payment button which can be used on your site\/app to trigger the payment page./i,
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
    renderApp(
      {},
      {
        session: {
          user: { isPaymentPagesEnabled: true },
          org: {
            custom_code: 'curlec',
            business_name: 'Curlec',
          },
        },
        onboarding,
      },
    );

    expect(screen.getByText('Curlec')).toBeInTheDocument();
  });

  test('should load curlec onboarding initial screen', () => {
    renderApp(null, {
      session: {
        user: { isPaymentPagesEnabled: true },
        org: {
          custom_code: 'curlec',
          business_name: 'Curlec',
        },
      },
      onboarding,
    });

    expect(
      screen.getByText(
        /Build a custom, branded payment page for your business in under 10 minutes and start accepting payments with zero integration and tech efforts./i,
      ),
    ).toBeInTheDocument();
  });
});
