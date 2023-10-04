import React from 'react';
import 'react-dates/initialize';
import '@testing-library/jest-dom/extend-expect';
import {
  onboarding,
  defaultProps,
  rzpUserConfig,
  paymentLinkStoreConfiguration,
} from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/fixtures';
import PaymentLink from 'merchant/views/PaymentLinks/Index';
import { render, screen, userEvent, waitFor } from 'test-utils';

describe('Payment Link', () => {
  /**
   * Setting 'user/merchant' window level configuration
   */
  beforeAll(() => {
    window.rzp_user = rzpUserConfig('activated', 'owner');
    window.scrollTo = jest.fn();
    jest.useFakeTimers();
  });

  afterEach(() => {
    jest.useRealTimers();
  });

  /*
   * @param {*} props = {}
   * @return <PaymentLink /> index file
   */
  const renderApp = ({ props, state } = {}) => {
    return render(<PaymentLink {...defaultProps} {...props} />, {
      showModal: true,
      initialState: {
        session: {
          user: { ...paymentLinkStoreConfiguration, ...state },
        },
      },
      initialEntries: ['/paymentlinks'],
      path: '/paymentlinks',
    });
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
    const { history } = renderApp();
    const batchUploadTab = screen.getByText('Batch Uploads');
    expect(batchUploadTab).toBeInTheDocument();
    await userEvent.click(batchUploadTab);
    expect(history.location.pathname).toEqual('/paymentlinks/batchuploads');
  });

  test('should load onboarding screen', () => {
    render(<PaymentLink />, {
      initialState: {
        session: {
          user: { ...paymentLinkStoreConfiguration, isOrgAxis: false },
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
          user: {
            ...paymentLinkStoreConfiguration,
            isOrgAxis: true,
            findTag: jest.fn(() => false),
          },
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
    await waitFor(() => {
      expect(screen.getByText('Send Payment Links Faster with the Mobile App')).toBeInTheDocument();
    });
  });

  test('should not load mobile pop up for i18n orgs', async () => {
    renderApp({ props: {}, state: { findTag: () => true } });
    await waitFor(() => {
      expect(
        screen.queryByText('Send Payment Links Faster with the Mobile App'),
      ).not.toBeInTheDocument();
    });
  });
});
