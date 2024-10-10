/* eslint-disable no-relative-import-paths/no-relative-import-paths */
import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, userEvent, waitFor, act } from 'test-utils';
import BounceMemoPopup from '../../BounceMemoPopup';
import { closeModal } from 'merchant_common/reducers/modals';
import { fetchBouncememo, fetchBounceMemoBulk } from '../../BounceMemo.types';

// Mocking the dependencies
jest.mock('merchant_common/reducers/modals', () => ({
  closeModal: jest.fn(),
}));

jest.mock('../../BounceMemo.types', () => ({
  fetchBouncememo: jest.fn(),
  fetchBounceMemoBulk: jest.fn(),
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

const PAYMENT_METHOD_OPTIONS = [
  { label: 'eMANDATE', value: 'emandate' },
  { label: 'eNACH', value: 'nach' },
];

// Mocking the dropdown options
jest.mock('../../constants', () => ({
  PAYMENT_METHOD_OPTIONS,
  ALL_OPTION: { label: 'All', value: 'all' },
}));

// Mock the Redux selector
jest.mock('react-redux', () => ({
  ...jest.requireActual('react-redux'),
  useSelector: jest.fn().mockImplementation((selector) => selector(mockState)),
}));

const renderApp = () => {
  render(<BounceMemoPopup paymentPage={'singlePage'} paymentID={'pay_12345'} />);
};

const renderAppBulk = () => {
  render(<BounceMemoPopup paymentPage={'failedPaymentPage'} paymentID={'pay_12345'} />);
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

// Mocking the DateRangePicker
jest.mock('common/ui/DateRangePicker', () => ({
  ...jest.requireActual('common/ui/DateRangePicker'),
  __esModule: true,
  default: () => {
    return <div>DateRangePicker Component</div>;
  },
}));

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
    await act(async () => {
      await userEvent.click(screen.getByTestId('bounce-memo-download-btn'));
    });

    await waitFor(() => {
      expect(
        screen.getByText(
          'Unable to fetch Bounce memo information at this moment, please try again later.',
        ),
      ).toBeInTheDocument();
    });

    expect(screen.queryByTestId('bounce-memo-download-btn')).not.toBeDisabled();
  });

  test('should handle fetch bounce memo', async () => {
    const mockParams = {
      paymentID: 'pay_12345', // get capturable amount
      merchantId: undefined,
    };
    fetchBouncememo.mockResolvedValueOnce(mockResponse);

    render(<BounceMemoPopup paymentPage="singlePage" paymentID="pay_12345" />);

    await act(async () => {
      await userEvent.click(screen.getByTestId('bounce-memo-download-btn'));
    });

    await waitFor(() => {
      expect(fetchBouncememo).toHaveBeenCalledWith(mockParams);
    });
  });

  test('should handle bulk bounce memo download with correct DateParams', async () => {
    const mockDateParams = {
      paymentMethods: undefined,
      paymentID: 'pay_12345',
    };

    // Mock the state to include date and payment method
    renderAppBulk();

    // Set the date range and payment method manually (can be done via UI interaction)
    await act(async () => {
      await userEvent.click(screen.getByText('Payment Method'));
      // Set the mock dates and initiate download
      await userEvent.click(screen.getByTestId('bounce-memo-download-btn-bulk'));
    });

    await waitFor(() => {
      // Expect the fetchBounceMemoBulk to be called with the correct DateParams
      expect(fetchBounceMemoBulk).toHaveBeenCalledWith(expect.objectContaining(mockDateParams));
    });
  });

  test('should show error notification when no bounce memo is available for the specified date range', async () => {
    const emptyResponse = {
      data: {
        data: [], // Empty array simulating no bounce memos available
      },
    };
    const mockDateParams = {
      paymentMethods: undefined,
      paymentID: 'pay_12345',
    };

    // Mock the fetchBounceMemoBulk function to return the empty response
    fetchBounceMemoBulk.mockResolvedValueOnce(emptyResponse);

    // Render the BounceMemoPopup component in the bulk download mode
    renderAppBulk(); // Ensure this renders the component with paymentPage as 'paymentPage'

    // Simulate user action to trigger the bounce memo download
    await act(async () => {
      await userEvent.click(screen.getByText('Payment Method'));
      await userEvent.click(screen.getByTestId('bounce-memo-download-btn-bulk'));
    });

    await waitFor(() => {
      // Expect the fetchBounceMemoBulk to be called with the correct DateParams
      expect(fetchBounceMemoBulk).toHaveBeenCalledWith(expect.objectContaining(mockDateParams));
    });

    // Wait for the asynchronous operations to complete and check the result
    await waitFor(() => {
      // Assert that the error notification is shown with the appropriate message
      expect(
        screen.getByText(/No bounce memo available for the specified date range/i),
      ).toBeInTheDocument();
    });

    // Assert that the submit button is not disabled (operation has completed)
    expect(screen.queryByTestId('bounce-memo-download-btn-bulk')).not.toBeDisabled();
  });
});
