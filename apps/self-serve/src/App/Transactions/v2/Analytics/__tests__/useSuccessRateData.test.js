import { renderApp } from './mocks/fixtures/useSuccessRateData';
import {
  expectFailedToBeHidden,
  expectFailedToBeVisible,
  expectLoadingToBeHidden,
  expectLoadingToBeVisible,
} from './mocks/fixtures/utils';
import { mockServerResponse } from './mocks/handlers/useSuccessRateData';
import { screen, waitFor, userEvent } from 'apps/self-serve/src/services/test/test-utils';

describe('useSuccessRateData', () => {
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
      expect(dataCallback).toHaveBeenCalledWith({
        successRateData: 99.99,
      });
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

  test("should give initial data when API response doesn't have sr", async () => {
    const dataCallback = jest.fn();
    mockServerResponse({
      kind: 'success',
      data: {
        sr: undefined,
      },
    });
    renderApp({
      dataCallback,
    });
    const fetch = screen.getByText('Fetch');
    await userEvent.click(fetch);
    await expectLoadingToBeVisible();
    await waitFor(() => {
      expect(dataCallback).toHaveBeenCalledWith({
        successRateData: 0,
      });
    });
    await expectLoadingToBeHidden();
  });
});
