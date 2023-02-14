import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen } from 'test-utils';
import { getUser } from 'merchant/store';
import { App } from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/CreateV1';

describe('Payment Link Create V1', () => {
  const onCloseMock = jest.fn();

  beforeAll(() => {
    window.hj = jest.fn();
    window.rzp_user = {};

    window.rzpQ = {
      component: jest.fn(),
      paymentLinks: () => ({
        interaction: jest.fn(),
      }),
    };
  });

  const user = getUser();
  const renderApp = (props = {}) => {
    return render(
      <App
        {...props}
        onClose={onCloseMock}
        isLoading={false}
        tracking={{
          trackEvent: jest.fn(),
        }}
      />,
      {
        initialState: {
          session: {
            user: {
              ...user,
              getPaymentLinkCustomizedFormFields: {
                receipt: {
                  label: 'Receipt No.',
                  placeholder: '',
                },
                description: {
                  label: 'Payment For',
                  placeholder: 'Payment Description',
                },
              },
              paymentLinkCreationFormExtraFields: [],
              isPaymentlinksV2Enabled: props.isPaymentlinksV2Enabled || false,
            },
          },
        },
      },
    );
  };

  test('CreateV1 component should be defined', () => {
    expect(App).toBeDefined();
  });

  test('should render "Create Payment Link" as title', () => {
    renderApp();
    expect(screen.getByText('Create Payment Link')).toBeInTheDocument();
  });

  test('should check for test mode declaration', () => {
    const props = {
      mode: 'test',
    };
    renderApp(props);
    expect(screen.getByText(/Test Mode/i)).toBeInTheDocument();
  });
});
