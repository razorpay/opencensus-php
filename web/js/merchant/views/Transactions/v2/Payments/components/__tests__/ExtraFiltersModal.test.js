import { act } from '@testing-library/react';

import { POS_TRANSACTION_CHANNEL } from 'merchant/views/Transactions/constants';
import ExtraFiltersModal from 'merchant/views/Transactions/v2/common/components/ExtraFiltersModal';
import * as ModalActions from 'merchant_common/reducers/modals';
import { render, screen, userEvent } from 'test-utils';

jest.setTimeout(30000);

const mockHandleSearch = jest.fn();

jest.mock('rc-tree-select', () => {
  const React = require('react');
  return {
    __esModule: true,
    default: ({ onChange, value }) => (
      <div>
        <button onClick={() => onChange(['store_id123', 'store_id124'])}>Select Store</button>
      </div>
    )
  };
});

jest.mock('merchant/containers/Home/RTUX/utils', () => ({
  isOmniHomepageEnabled: jest.fn().mockReturnValue(true),
}));

const renderExtraFiltersModal = () => {
  const defaultProps = {
    handleSearch: mockHandleSearch,
  };
  return render(<ExtraFiltersModal {...defaultProps} />);
};

describe('ExtraFiltersModal', () => {
  const closeModalSpy = jest.spyOn(ModalActions, 'closeModal');
  beforeEach(() => {
    closeModalSpy.mockClear();
  });

  test('should render Payment Method and Channel filters', () => {
    renderExtraFiltersModal();
    expect(screen.getByText('Payment Method')).toBeInTheDocument();
    expect(screen.getByText('Channel')).toBeInTheDocument();
  });

  test('should update Payment Method option to "Card" when selected', async () => {
    renderExtraFiltersModal();

    const dropdownTrigger = screen.getByPlaceholderText('Select Payment Method');

    expect(dropdownTrigger).toBeInTheDocument();
    await userEvent.click(dropdownTrigger);

    const cardOption = screen.getByRole('option', { name: 'Card' });
    expect(cardOption).toBeInTheDocument();
    await userEvent.click(cardOption);

    const selectedOption = screen.getByPlaceholderText('Select Payment Method');
    expect(selectedOption).toHaveValue('Card');

    expect(selectedOption).toHaveTextContent('Card');
  });

  test('should update Source Channel option to "In Person" when Selected', async () => {
    renderExtraFiltersModal();
    const paymentSourceChannel = screen.getByPlaceholderText('Select Source Channel');
    expect(paymentSourceChannel).toBeInTheDocument();
    await userEvent.click(paymentSourceChannel);

    const inPersonOption = screen.getByRole('option', { name: 'In Person' });
    expect(inPersonOption).toBeInTheDocument();
    await userEvent.click(inPersonOption);

    const selectedOption = screen.getByPlaceholderText('Select Source Channel');
    expect(selectedOption).toHaveValue('In Person');

    expect(selectedOption).toHaveTextContent('In Person');
  });

  test('should do a search query for transactions with applied filters upon clicking Apply', async () => {
    renderExtraFiltersModal();
    const dropdownTrigger = screen.getByPlaceholderText('Select Payment Method');
    expect(dropdownTrigger).toBeInTheDocument();
    await act(async () => {
      await userEvent.click(dropdownTrigger);
    });

    const cardOption = screen.getByRole('option', { name: 'Card' });
    expect(cardOption).toBeInTheDocument();
    await act(async () => {
      await userEvent.click(cardOption);
    });

    const paymentSourceChannel = screen.getByPlaceholderText('Select Source Channel');
    await act(async () => {
      await userEvent.click(paymentSourceChannel);
    });

    const inPersonOption = screen.getByRole('option', { name: 'In Person' });
    expect(inPersonOption).toBeInTheDocument();
    await act(async () => {
      await userEvent.click(inPersonOption);
    });

    const deviceIdInput = screen.getByPlaceholderText('Search');
    expect(deviceIdInput).toBeInTheDocument();
    await act(async () => {
      await userEvent.type(deviceIdInput, 'device_id123');
    });


    const storeIdInput = screen.getByText('Hierarchy Level');
    expect(storeIdInput).toBeInTheDocument();
    await act(async () => {
      await userEvent.click(screen.getByText('Select Store'));
    });

    const applyButton = screen.getByRole('button', { name: 'Apply' });
    expect(applyButton).toBeInTheDocument();
    await act(async () => {
      await userEvent.click(applyButton);
    });

    expect(mockHandleSearch).toHaveBeenCalledWith({
      method: ['card'],
      source_channel: [POS_TRANSACTION_CHANNEL],
      device_id: 'device_id123',
      'store_ids[]': ['store_id123', 'store_id124'],
    });
  });

  test('should call closeModal on Cancel', async () => {
    renderExtraFiltersModal();
    const cancelButton = screen.getByRole('button', { name: 'Cancel' });
    await userEvent.click(cancelButton);
    expect(closeModalSpy).toHaveBeenCalled();
  });
});
