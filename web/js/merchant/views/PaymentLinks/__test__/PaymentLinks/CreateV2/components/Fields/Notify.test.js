import React from 'react';
import { render, screen } from 'test-utils';
import Notify from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/Fields/Notify';
import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track';

jest.spyOn(track.lj.fields, 'notifySms').mockImplementation(() => {});
jest.spyOn(track.segment.fields, 'notifySms').mockImplementation(() => {});
jest.spyOn(track.lj.fields, 'notifyEmail').mockImplementation(() => {});
jest.spyOn(track.segment.fields, 'notifyEmail').mockImplementation(() => {});

describe('Notify Component Unit test', () => {
  beforeAll(() => {
    window.rzp_user = {};

    window.rzpQ = {
      component: jest.fn(),
      paymentLinks: () => ({
        interaction: jest.fn(),
      }),
      productOnboarding: () => ({
        success: jest.fn(),
      }),
    };
  });

  const renderApp = (props = {}) => {
    return render(<Notify {...props} />, {
      initialState: {
        session: {
          user: {
            findTag: () => false,
            isOrgAllowedFunctionality: () => true,
            merchant: {
              product_international: '0000000000',
            },
          },
        },
      },
    });
  };

  test('should render Notify Component with "Email", "SMS", "More ways" as different modes', () => {
    renderApp();
    expect(screen.getByText('Notify via Email')).toBeInTheDocument();
    expect(screen.getByText('Notify via SMS')).toBeInTheDocument();
    expect(screen.getByText('More ways to notify')).toBeInTheDocument();
  });
});
