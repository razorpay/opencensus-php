import React from 'react';
import { useToast } from '@razorpay/blade/components';

import { userEvent } from '@apps/digital-bills/src/services/test/test-utils';
import BillInfo from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/BillInfo';
import { useBillDetailsStore } from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/stores/billDetailsStore';
import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import {
  userInfo,
  billInfo,
} from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/__tests__/mocks';

const props = { userInfo, billInfo };

jest.mock('@razorpay/blade/components', () => {
  const originalModule = jest.requireActual('@razorpay/blade/components');
  return {
    ...originalModule,
    useToast: jest.fn(),
  };
});

// Mock 'useBillDetailsStore' Zustand store
jest.mock('../../stores/billDetailsStore', () => ({
  useBillDetailsStore: jest.fn().mockReturnValue({}),
}));

const showMock = jest.fn();

beforeEach(() => {
  (useToast as unknown as jest.Mock).mockReturnValue({
    show: showMock,
  });
});

afterEach(() => {
  jest.clearAllMocks();
});

describe('BillInfo', () => {
  test('should render the BillInfo component', () => {
    const { getByText } = renderWithWrappers(<BillInfo {...props} />);
    expect(getByText('Bill Details')).toBeInTheDocument();
    expect(getByText('+91-1234 5678 21')).toBeInTheDocument();
    expect(getByText('email@email.com')).toBeInTheDocument();
    expect(getByText('Digital')).toBeInTheDocument();
    expect(getByText('bill_kmkejlsdcg8hr')).toBeInTheDocument();
  });

  test('should render the BillInfo component with bill details buttons', () => {
    const { getByTestId } = renderWithWrappers(<BillInfo {...props} />);

    const deleteBtn = getByTestId('delete-btn');
    const resendBillBtn = getByTestId('resend-bill-btn');
    expect(deleteBtn).toBeInTheDocument();
    expect(resendBillBtn).toBeInTheDocument();
  });

  test('should trigger onDeleteModalOpen on clicking the delete button', async () => {
    const onDeleteModalOpen = jest.fn();
    (useBillDetailsStore as unknown as jest.Mock).mockReturnValue({
      onDeleteModalOpen,
    });
    const { getByTestId } = renderWithWrappers(<BillInfo {...props} />);
    const deleteBtn = getByTestId('delete-btn');
    await userEvent.click(deleteBtn);
    expect(onDeleteModalOpen).toHaveBeenCalledTimes(1);
  });

  test('should triggers onResendModalOpen on clicking the resend button', async () => {
    const onResendModalOpen = jest.fn();
    (useBillDetailsStore as unknown as jest.Mock).mockReturnValue({
      onResendModalOpen,
    });
    const { getByTestId } = renderWithWrappers(<BillInfo {...props} />);
    const resendBillBtn = getByTestId('resend-bill-btn');
    await userEvent.click(resendBillBtn);
    expect(onResendModalOpen).toHaveBeenCalledTimes(1);
  });
});
