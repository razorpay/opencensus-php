/* eslint-disable import/order */
import { renderApp } from './mocks/fixtures/PaymentsListFilter';
import { screen, userEvent, waitFor } from 'apps/self-serve/src/services/test/test-utils';
import 'jest-location-mock';

export const mockOpenModal = jest.fn();

jest.mock('shell/commonStore', () => ({
  ...jest.requireActual('shell/commonStore'),
  useStore: (callbackFn) =>
    callbackFn({
      session: {
        user: {
          isOmniChannelMerchant: true,
          pos_activation_status: 'under_review',
        },
      },
      openModal: mockOpenModal,
    }),
}));

describe('PaymentsListFilter Actions', () => {
  describe('Extra Filters for Omni Merchants', () => {
    test('should open extra filters modal when clicked on All Filters', async () => {
      renderApp({}, {});
      const extraFiltersButton = screen.getByRole('button', { name: 'All Filters' });
      expect(extraFiltersButton).toBeInTheDocument();
      await userEvent.click(extraFiltersButton);
      await waitFor(() => {
        expect(mockOpenModal).toHaveBeenCalled();
      });
    });
  });
});
