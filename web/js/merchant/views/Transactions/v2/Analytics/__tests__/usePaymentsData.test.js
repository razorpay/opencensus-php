import { screen, waitFor, userEvent } from 'test-utils';
import {
  renderApp,
  expectedHookResponse,
  mockServerResponsePayload,
} from './mocks/fixtures/usePaymentsData';
import {
  expectFailedToBeHidden,
  expectFailedToBeVisible,
  expectLoadingToBeHidden,
  expectLoadingToBeVisible,
} from './mocks/fixtures/utils';
import { mockServerResponse } from './mocks/handlers/usePaymentsData';

describe('usePaymentsData', () => {
  test('should give valid data and toggle off loading', async () => {
    const dataCallback = jest.fn();
    mockServerResponse({
      kind: 'success',
    });
    renderApp({
      dataCallback,
      isRefundPendingEnabled: true,
    });
    const fetch = screen.getByText('Fetch');
    await userEvent.click(fetch);
    await expectLoadingToBeVisible();
    await waitFor(() => {
      expect(dataCallback).toHaveBeenCalledWith(
        expectedHookResponse.withRefundPendingFeatureDisabled,
      );
    });
    await expectLoadingToBeHidden();
  });

  test('with feature flag should give valid data and toggle off loading', async () => {
    const dataCallback = jest.fn();
    mockServerResponse({
      kind: 'success',
    });
    renderApp({
      dataCallback,
      isRefundPendingEnabled: false,
    });
    const fetch = screen.getByText('Fetch');
    await userEvent.click(fetch);
    await expectLoadingToBeVisible();

    await waitFor(() => {
      expect(dataCallback).toHaveBeenCalledWith(
        expectedHookResponse.withRefundPendingFeatureEnabled,
      );
    });
    await expectLoadingToBeHidden();
  });

  test('should toggle on failed when API response is not valid', async () => {
    mockServerResponse({
      kind: 'failure',
    });
    renderApp();
    const fetch = screen.getByText('Fetch');
    await expectFailedToBeHidden();
    await userEvent.click(fetch);
    await expectLoadingToBeVisible();

    await expectFailedToBeVisible();
    await expectLoadingToBeHidden();
  });

  test('should give default value for paymentCapturedCount and paymentCapturedAmount when there is no captured data', async () => {
    const dataCallback = jest.fn();
    mockServerResponse(mockServerResponsePayload.no_capture_data);
    renderApp({
      dataCallback,
      isRefundPendingEnabled: false,
    });
    const fetch = screen.getByText('Fetch');
    await userEvent.click(fetch);
    await expectLoadingToBeVisible();
    await waitFor(() => {
      expect(dataCallback).toHaveBeenCalledWith(expectedHookResponse.no_capture_data);
    });
    await expectLoadingToBeHidden();
  });

  test('should show all top 4 payment methods when there are only 4 methods in response', async () => {
    const dataCallback = jest.fn();
    mockServerResponse(mockServerResponsePayload.top_4_methods);
    renderApp({
      dataCallback,
      isRefundPendingEnabled: false,
    });
    const fetch = screen.getByText('Fetch');
    await userEvent.click(fetch);
    await expectLoadingToBeVisible();
    await waitFor(() => {
      expect(dataCallback).toHaveBeenCalledWith(expectedHookResponse.top_4_methods);
    });
    await expectLoadingToBeHidden();
  });

  test('should show only those payment methods which have positive value', async () => {
    const dataCallback = jest.fn();
    mockServerResponse(mockServerResponsePayload.zero_value_methods);
    renderApp({
      dataCallback,
      isRefundPendingEnabled: false,
    });
    const fetch = screen.getByText('Fetch');
    await userEvent.click(fetch);
    await expectLoadingToBeVisible();
    await waitFor(() => {
      expect(dataCallback).toHaveBeenCalledWith(expectedHookResponse.zero_value_methods);
    });
    await expectLoadingToBeHidden();
  });

  test('should give default values when api response doesnt have data', async () => {
    const dataCallback = jest.fn();
    mockServerResponse(mockServerResponsePayload.no_data);
    renderApp({
      dataCallback,
      isRefundPendingEnabled: false,
    });
    const fetch = screen.getByText('Fetch');
    await userEvent.click(fetch);
    await expectLoadingToBeVisible();
    await waitFor(() => {
      expect(dataCallback).toHaveBeenCalledWith(expectedHookResponse.no_data);
    });
    await expectLoadingToBeHidden();
  });
});
