import React from 'react';
import store from 'merchant/store';
import '@testing-library/jest-dom/extend-expect';
import {
  onboarding,
  updateUser,
  defaultProps,
  rzpUserConfig,
  paymentLinkStoreConfiguration,
} from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/fixtures';
import PaymentLink from 'merchant/views/PaymentLinks/Index';
import { render, screen, userEvent } from 'test-utils';

const getStateSpy = jest.spyOn(store, 'getState');

describe('Payment Link', () => {
  /*
   * Mocking 'user/merchant' level configuration from redux store.
   */
  updateUser(getStateSpy, paymentLinkStoreConfiguration);

  /**
   * Setting 'user/merchant' window level configuration
   */
  beforeAll(() => {
    window.rzp_user = rzpUserConfig('activated', 'owner');
    jest.useFakeTimers();
  });

  afterEach(() => {
    jest.useRealTimers();
  });

  /*
   * @param {*} props = {}
   * @return <PaymentLink /> index file
   */
  const renderApp = ({ props } = {}) => {
    return render(<PaymentLink {...defaultProps} {...props} />, { showModal: true });
  };

  test('should render component without errors', () => {
    expect(renderApp).not.toThrowError();
  });

  test('should render payment link title', () => {
    renderApp();
    expect(screen.getByText('Payment Links')).toBeInTheDocument();
  });

  test('should render "Know more" link', () => {
    renderApp();
    expect(screen.getByText('Know more')).toBeInTheDocument();
  });

  test('should render batch uploads', async () => {
    renderApp();
    userEvent.click(await screen.findByText('Batch Uploads'));
    expect(screen.getByText('Batch Uploads')).toBeInTheDocument();
  });

  test('should load onboarding screen', () => {
    render(<PaymentLink />, {
      initialState: {
        session: {
          user: { isOrgAxis: false },
        },
        onboarding,
      },
    });
    expect(screen.getByText('Payment Links Onboarding Flow')).toBeInTheDocument();
  });

  test('should not load onboarding screen if orgAxis is enabled', () => {
    render(<PaymentLink />, {
      initialState: {
        session: {
          user: { isOrgAxis: true },
          org: {
            custom_code: 'rzp',
          },
        },
        onboarding,
      },
    });
    expect(screen.queryByText('Payment Links Onboarding Flow')).not.toBeInTheDocument();
  });

  test('should load mobile pop up', async () => {
    renderApp();
    expect(
      await screen.findByText('Send Payment Links Faster with the Mobile App'),
    ).toBeInTheDocument();
  });
});
