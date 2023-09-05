import { screen, waitFor, userEvent } from 'test-utils';
import { renderApp } from './mocks/fixtures/useDisputesData';
import {
  expectFailedToBeHidden,
  expectFailedToBeVisible,
  expectLoadingToBeHidden,
  expectLoadingToBeVisible,
} from './mocks/fixtures/utils';
import { mockServerResponse } from './mocks/handlers/useDisputesData';

describe('useDisputesData', () => {
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
        disputeData: {
          openDisputesCount: 124,
          underReviewDisputesCount: 124,
          totalDisputeAmount: 33454,
        },
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
      data: {},
    });
    renderApp({
      dataCallback,
    });
    const fetch = screen.getByText('Fetch');
    await userEvent.click(fetch);
    await expectLoadingToBeVisible();
    await waitFor(() => {
      expect(dataCallback).toHaveBeenCalledWith({
        disputeData: {
          openDisputesCount: 0,
          underReviewDisputesCount: 0,
          totalDisputeAmount: 0,
        },
      });
    });
    await expectLoadingToBeHidden();
  });
});
