import React from 'react';
import { render, fireEvent } from 'test-utils';
import CardConfigurationModal from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CardConfiguration/CardConfigurationModal';

describe('CardConfigurationModal', () => {
  const props = {
    isOpen: true,
    onClose: jest.fn(),
    cardProvider: [
      { name: 'Visa', isEnabled: true, icon: 'visa' },
      { name: 'MasterCard', isEnabled: true, icon: 'mastercard' },
    ],
    cardIssuer: [
      { name: 'HDFC', isEnabled: true },
      { name: 'ICICI', isEnabled: true },
    ],
    currentConfig: [{ method: 'card', types: [], issuers: [], networks: [] }],
  };

  it('renders correctly', () => {
    const { getByText, getByLabelText } = render(<CardConfigurationModal {...props} />);
    expect(getByText('Domestic cards')).toBeInTheDocument();
    expect(getByLabelText('Select how you want to add cards')).toBeInTheDocument();
  });

  it('handles chip group selection', () => {
    const { getByText, getByLabelText } = render(<CardConfigurationModal {...props} />);
    const chipGroup = getByLabelText('Select how you want to add cards');
    const cardTypeElement = getByText('Card Type');
    fireEvent.click(cardTypeElement);
    expect(chipGroup).toContainElement(cardTypeElement);
    const cardIssuerElement = getByText('Card Issuer');
    fireEvent.click(cardIssuerElement);
    expect(chipGroup).toContainElement(cardIssuerElement);
  });

  it('renders CardType component when Card Type is selected', () => {
    const { getByText } = render(<CardConfigurationModal {...props} />);
    fireEvent.click(getByText('Card Type'));
    expect(getByText(/Debit/i)).toBeInTheDocument();
    expect(getByText(/Credit/i)).toBeInTheDocument();
  });

  it('renders BinNumber component when BIN Number is selected', () => {
    const { getAllByText } = render(<CardConfigurationModal {...props} />);
    fireEvent.click(getAllByText('BIN')[0]);
    expect(getAllByText('BIN')[0]).toBeInTheDocument();
  });

  it('enables save button when configuration changes', () => {
    const { getByText } = render(<CardConfigurationModal {...props} />);
    fireEvent.click(getByText('Card Type'));
    expect(getByText(/Debit/));
  });
});
