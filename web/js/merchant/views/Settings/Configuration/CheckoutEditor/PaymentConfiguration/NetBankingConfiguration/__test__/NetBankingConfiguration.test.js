import React from 'react';
import { render, userEvent, fireEvent } from 'test-utils';
import NetBankingConfigurationModal from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/NetBankingConfiguration/NetBankingConfigurationModal';

describe('NetBankingConfigurationModal', () => {
  const props = {
    isOpen: true,
    onClose: jest.fn(),
    currentConfig: [{ method: 'netbanking', banks: [] }],
  };

  it('renders modal with correct title and subtitle', () => {
    const { getByText } = render(<NetBankingConfigurationModal {...props} />);
    expect(getByText('Netbanking')).toBeInTheDocument();
    expect(getByText('Retail and corporate banks')).toBeInTheDocument();
  });

  it('calls onClose when cancel button is clicked', async () => {
    const { getByText } = render(<NetBankingConfigurationModal {...props} />);
    const cancelButton = getByText('Cancel');
    await userEvent.click(cancelButton);
    expect(props.onClose).toHaveBeenCalled();
  });

  it('enables save button when changes are made', () => {
    const { getByPlaceholderText, getByText } = render(
      <NetBankingConfigurationModal {...props} />,
    );
    const searchInput = getByPlaceholderText('Search for banks');
    fireEvent.change(searchInput, { target: { value: 'HDFC' } });
    const saveButton = getByText('Save');
    expect(saveButton).not.toBeDisabled();
  });
});
