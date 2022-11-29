import React from 'react';
import { render, userEvent, waitFor, server } from 'test-utils';
import { rest } from 'msw';
import merge from 'lodash/merge';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import PaymentHandleModal from 'merchant/containers/Home/ProductOnboardingCard/partials/PaymentHandleModal';
import { paymentHandleData, CURRENCY_LIST } from './mocks/fixtures';
import * as analytics from 'common/utils/analytics';

let analyticsTrackSpy, mockCloseModal, mockShowNotification;

const state = {
  session: {
    user: {
      merchant: {
        product_international: '0000000000',
      },
    },
  },
};

describe('ProductOnboardingCard - PaymentHandleModal', () => {
  const renderApp = ({ initialState, ...rest } = {}) =>
    render(
      <Provider store={storeWithInitialState(merge(state, initialState))}>
        <PaymentHandleModal
          product="PG"
          showNotification={mockShowNotification}
          closeModal={mockCloseModal}
          paymentHandleData={paymentHandleData}
          {...rest}
        />
      </Provider>,
    );

  beforeAll(() => {
    window.currencyList = CURRENCY_LIST;
    document.execCommand = jest.fn();
    analyticsTrackSpy = jest.spyOn(analytics, 'analyticsTrack');
    mockCloseModal = jest.fn();
    mockShowNotification = jest.fn();
  });
  beforeEach(() => {
    jest.clearAllMocks();
  });
  test('should render component without errors', () => {
    expect(renderApp).not.toThrowError();
  });

  test('should show correct payment handle', () => {
    const { queryByText } = renderApp();
    expect(queryByText(paymentHandleData.paymentHandleSlug.slice(1))).toBeVisible();
  });

  test('should show notification on copy link', async () => {
    const { getByRole } = renderApp({ product: 'PH' });
    const cta = getByRole('button', { name: /copy link/i });

    expect(cta).toBeEnabled();
    await userEvent.click(cta);
    expect(analyticsTrackSpy).toHaveBeenCalledTimes(1);
    expect(analyticsTrackSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Dashboard PH Copy',
        properties: { product: 'PH', section: 'CustomModal' },
      }),
    );
    expect(mockShowNotification).toHaveBeenCalledTimes(1);
    expect(mockShowNotification).toHaveBeenCalledWith(expect.objectContaining({ type: 'success' }));
    expect(mockCloseModal).toHaveBeenCalledTimes(1);
  });

  test('should show success notification on amount update', async () => {
    const { getByRole } = renderApp({ product: 'PH' });

    await userEvent.type(getByRole('textbox'), '20');
    const cta = getByRole('button', { name: /save and copy link/i });
    expect(cta).toBeEnabled();

    await userEvent.click(cta);
    await waitFor(() => {
      expect(mockShowNotification).toHaveBeenCalledTimes(1);
      expect(mockShowNotification).toHaveBeenCalledWith(
        expect.objectContaining({ type: 'success' }),
      );
    });
    expect(mockCloseModal).toHaveBeenCalledTimes(1);
  });

  test('should show error message on amount update API error', async () => {
    server.use(
      rest.post('*/merchant/api/:mode/payment_handle/custom_amount', (req, res, ctx) => {
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        return res(ctx.errors(['Some error occurred']), ctx.delay(50));
      }),
    );

    const { getByRole } = renderApp({ product: 'PG' });

    await userEvent.type(getByRole('textbox'), '20');
    const cta = getByRole('button', { name: /save and copy link/i });
    expect(cta).toBeEnabled();

    await userEvent.click(cta);
    await waitFor(() => {
      expect(mockShowNotification).toHaveBeenCalledTimes(1);
      expect(mockShowNotification).toHaveBeenCalledWith(expect.objectContaining({ type: 'error' }));
    });
  });

  test('should close modal on clicking close', async () => {
    const { getByTestId } = renderApp({ product: 'PH' });

    await userEvent.click(getByTestId('modal-header-close-btn'));
    expect(mockCloseModal).toHaveBeenCalledTimes(1);
  });
});
