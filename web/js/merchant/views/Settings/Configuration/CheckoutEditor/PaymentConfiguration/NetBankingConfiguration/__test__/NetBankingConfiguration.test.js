import React from 'react';
import { render, fireEvent } from 'test-utils';
import NetBankingConfigurationModal from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/NetBankingConfiguration/NetBankingConfigurationModal';

describe('NetBankingConfigurationModal', () => {
  const props = {
    isOpen: true,
    onClose: jest.fn(),
    netBankingList: [
      { name: 'HDFC', slug: 'hdfc', type: 'retail' },
      { name: 'ICICI', slug: 'icici', type: 'retail' },
      { name: 'SBI', slug: 'sbi', type: 'corporate' },
    ],
    currentConfig: [{ method: 'netbanking', banks: [] }],
  };

  it('renders modal with correct title and subtitle', () => {
    const { getByText } = render(<NetBankingConfigurationModal {...props} />);
    expect(getByText('Netbanking')).toBeInTheDocument();
    expect(getByText('Retail and corporate banks')).toBeInTheDocument();
  });

  it('renders search input and chip group', () => {
    const { getByPlaceholderText, getByText } = render(<NetBankingConfigurationModal {...props} />);
    expect(getByPlaceholderText('Search for banks')).toBeInTheDocument();
    expect(getByText('Retail banks')).toBeInTheDocument();
    expect(getByText('Corporate banks')).toBeInTheDocument();
  });

  describe('NetBankingConfigurationModal', () => {
    const props = {
      isOpen: true,
      onClose: jest.fn(),
      netBankingList: [
        { name: 'HDFC', slug: 'hdfc', type: 'retail' },
        { name: 'ICICI', slug: 'icici', type: 'retail' },
        { name: 'SBI', slug: 'sbi', type: 'corporate' },
      ],
      currentConfig: [{ method: 'netbanking', banks: [] }],
    };

    it('renders modal with correct title and subtitle', () => {
      const { getByText } = render(<NetBankingConfigurationModal {...props} />);
      expect(getByText('Netbanking')).toBeInTheDocument();
      expect(getByText('Retail and corporate banks')).toBeInTheDocument();
    });

    it('renders search input and chip group', () => {
      const { getByPlaceholderText, getByText } = render(
        <NetBankingConfigurationModal {...props} />,
      );
      expect(getByPlaceholderText('Search for banks')).toBeInTheDocument();
      expect(getByText('Retail banks')).toBeInTheDocument();
      expect(getByText('Corporate banks')).toBeInTheDocument();
    });

    it('calls onClose when cancel button is clicked', () => {
      const { getByText } = render(<NetBankingConfigurationModal {...props} />);
      const cancelButton = getByText('Cancel');
      fireEvent.click(cancelButton);
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
});
