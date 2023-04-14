import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { App, onboarding } from 'merchant/views/Invoices/__tests__/mocks/fixtures/Onboarding';
import { render, screen, userEvent } from 'test-utils';
import { LANDING_PAGE_DESC } from 'merchant/views/Invoices/OnBoarding';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';

describe('Invoices Onboarding Screen', () => {
  /*
   * @param {*} props = {}
   * @return <New /> component file
   */
  beforeAll(() => {
    window.rzp_user = {};

    window.rzpQ = {
      component: jest.fn(),
      invoice: () => ({
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
          user: { isInvoicesEnabled: true },
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

  test('should load feature page of Invoice onboarding module', async () => {
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
    expect(screen.getByText(/GST compliant/i)).toBeInTheDocument();
    expect(
      screen.getByText(
        'Add GST, discounts and shipping details, all in an invoice and let our invoicing solution do the calculation for you.',
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        /Enable partial payments for your customers at the time of invoice creation directly from the dashboard./i,
      ),
    ).toBeInTheDocument();
    expect(screen.getByText(/Download Option/i)).toBeInTheDocument();
    expect(
      screen.getByText(
        /Let your customers save and download .pdf version of invoices for future reference./i,
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
        user: { isInvoicesEnabled: true },
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
        user: { isInvoicesEnabled: true },
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
