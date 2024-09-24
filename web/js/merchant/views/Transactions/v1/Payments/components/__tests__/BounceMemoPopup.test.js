/* eslint-disable no-relative-import-paths/no-relative-import-paths */
import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, userEvent, waitFor, act } from 'test-utils';
import BounceMemoPopup from '../../BounceMemoPopup';
import { closeModal } from 'merchant_common/reducers/modals';
import { fetchBouncememo } from '../../BounceMemo.types';

// Mocking the dependencies
jest.mock('merchant_common/reducers/modals', () => ({
  closeModal: jest.fn(),
}));

jest.mock('../../BounceMemo.types', () => ({
  fetchBouncememo: jest.fn(),
}));

jest.mock('../PdfCreation', () => jest.fn());

const mockUser = {
  id: 'mid_12345', // Mock the merchantId here
  name: 'Test Merchant',
};

const mockState = {
  session: {
    user: mockUser, // Add this to the session user
  },
};

// Mock the Redux selector
jest.mock('react-redux', () => ({
  ...jest.requireActual('react-redux'),
  useSelector: jest.fn().mockImplementation((selector) => selector(mockState)),
}));

const renderApp = () => {
  render(<BounceMemoPopup paymentID={'pay_12345'} />);
};

const mockResponse = {
  data: {
    data: [
      {
        umrn: 'NACH001',
        mandate_id: 'token_id',
        amount: '1000',
        date_submitted: 1725956592,
        date_of_failure: 1725956592,
        failure_reason: 'failure_reason_1',
        utility_code: 'utility_code_1',
        destination_bank: 'bank_1',
        merchant_name: 'merchant_name_1',
        customer_name: 'customer_name_1',
        ifsc: 'ifsc_1',
        account_number: 'account_number_1',
        payment_id: 'OjMTYEKEzN1J46',
      },
    ],
  },
};

describe('BounceMemoPopup Component', () => {
  beforeEach(() => {
    jest.clearAllMocks(); // Reset all mocks before each test
  });

  test('should close bounce memo modal', async () => {
    renderApp();
    await userEvent.click(screen.getByTestId('bounce-memo-modal-cancel'));
    await waitFor(() => {
      expect(closeModal).toHaveBeenCalledTimes(1);
    });
  });

  test('should show alert message', () => {
    renderApp();
    expect(screen.getByTestId('bounce-memo-modal-alert')).toBeInTheDocument();
  });

  test('should show error message if fetching bounce memo fails', async () => {
    fetchBouncememo.mockRejectedValueOnce(new Error('Fetch error'));

    renderApp();
    act(() => {
      userEvent.click(screen.getByTestId('bounce-memo-download-btn'));
    });

    await waitFor(() => {
      expect(
        screen.getByText(
          'Unable to fetch Bounce memo information at this moment, please try again later.',
        ),
      ).toBeInTheDocument();
    });
  });

  test('should handle fetch bounce memo', async () => {
    fetchBouncememo.mockResolvedValueOnce(mockResponse);

    render(<BounceMemoPopup paymentID="pay_12345" />);

    await userEvent.click(screen.getByTestId('bounce-memo-download-btn'));

    await waitFor(() => {
      expect(fetchBouncememo).toHaveBeenCalledWith('pay_12345');
    });
  });
});
