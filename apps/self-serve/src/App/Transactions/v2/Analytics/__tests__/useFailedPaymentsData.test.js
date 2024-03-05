import {
  expectedHookResponse,
  expectedInvalidHookResponse,
  invalidApiResponse,
  renderApp,
} from './mocks/fixtures/useFailedPaymentsData';
import {
  expectFailedToBeHidden,
  expectFailedToBeVisible,
  expectLoadingToBeHidden,
  expectLoadingToBeVisible,
} from './mocks/fixtures/utils';
import { mockServerResponse } from './mocks/handlers/useFailedPaymentsData';
import { screen, waitFor, userEvent } from 'apps/self-serve/src/services/test/test-utils';

describe('useFailedPaymentsData', () => {
  test('should give valid data and toggle off loading', async () => {
    const dataCallback = jest.fn();
    mockServerResponse({
      kind: 'success',
    });
    renderApp({
      dataCallback,
    });
    const fetch = screen.getByText('Fetch');
    await userEvent.click(fetch);
    await expectLoadingToBeVisible();
    await waitFor(() => {
      expect(dataCallback).toHaveBeenCalledWith(expectedHookResponse);
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

  test('should show intial values when API response is invalid', async () => {
    const dataCallback = jest.fn();
    mockServerResponse({
      kind: 'success',
      data: invalidApiResponse,
    });
    renderApp({
      dataCallback,
    });
    const fetch = screen.getByText('Fetch');
    await userEvent.click(fetch);
    await expectLoadingToBeVisible();
    await waitFor(() => {
      expect(dataCallback).toHaveBeenCalledWith(expectedInvalidHookResponse);
    });
    await expectLoadingToBeHidden();
  });
});
