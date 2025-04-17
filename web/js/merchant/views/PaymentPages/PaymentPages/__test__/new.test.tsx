import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, userEvent } from 'test-utils';
import PaymentPagesNew from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/New';

const renderApp = (isRazorx = true) =>
  render(<PaymentPagesNew />, {
    initialState: {
      session: {
        user: {
          isPaymentPageStorefrontEnabled: isRazorx,
          merchant: {
            currency: 'INR',
            country_code: 'IN',
          },
        },
        org: {
          merchant_styles: {
            checkout_theme_color: '#999999',
          },
        },
      },
    },
  });

// make a specific variable unmodifyable. Other fields continue to work as before.
Object.defineProperty(window, 'PP_ECOMMERCE_URL', {
  value: 'https://example.com',
  writable: false,
});

describe('Payment Pages -> Create (razorx on)', () => {
  beforeAll(() => {
    window.rzp_user = {};
  });

  test('should render template selection screen', () => {
    renderApp();
    // test if mock is working properly
    expect(window.PP_ECOMMERCE_URL).toBe('https://example.com');
    expect(screen.getByText('Select page of your choice')).toBeInTheDocument();
    expect(screen.getByText('Select Razorpay Webstore')).toBeInTheDocument();
    expect(screen.getByText('Select Payment page')).toBeInTheDocument();
  });

  test('should open create storefront screen on selecting storefront template', async () => {
    renderApp();

    const storefrontTemplateButton = screen.getByText('Select Razorpay Webstore');
    expect(storefrontTemplateButton).toBeInTheDocument();

    await userEvent.click(storefrontTemplateButton);

    expect(storefrontTemplateButton).not.toBeInTheDocument();
    expect(screen.getByText('Create a new Razorpay Webstore')).toBeInTheDocument();
  });

  test('should open create payment pages screen on selecting payment pages template', async () => {
    renderApp();

    const paymentPagesTemplateButton = screen.getByText('Select Payment page');
    expect(paymentPagesTemplateButton).toBeInTheDocument();
    await userEvent.click(paymentPagesTemplateButton);

    expect(paymentPagesTemplateButton).not.toBeInTheDocument();
    // PP Templates modal must not exist
    expect(screen.queryByText('Choose from the templates')).not.toBeInTheDocument();
    expect(screen.getByText('Create New Payment Page')).toBeInTheDocument();
  });
});

describe('Payment Pages -> Create (razorx off)', () => {
  test('should not render template selection screen', () => {
    renderApp(false);
    expect(screen.queryByText('Select page of your choice')).not.toBeInTheDocument();
    expect(screen.queryByText('Select Razorpay Webstore')).not.toBeInTheDocument();
  });

  test('should render PP templates popup', () => {
    renderApp(false);

    expect(screen.getByText('Choose from the templates')).toBeInTheDocument();
    expect(screen.getByText('Events and Tickets')).toBeInTheDocument();
  });

  // test.skip('should open create payment pages screen on selecting payment pages template', async () => {
  //   renderApp();

  //   const paymentPagesTemplateButton = screen.getByText('Select Payment page');
  //   expect(paymentPagesTemplateButton).toBeInTheDocument();
  //   await userEvent.click(paymentPagesTemplateButton);

  //   expect(paymentPagesTemplateButton).not.toBeInTheDocument();
  //   expect(screen.getByText('Choose from the templates')).toBeInTheDocument();
  // });
});
