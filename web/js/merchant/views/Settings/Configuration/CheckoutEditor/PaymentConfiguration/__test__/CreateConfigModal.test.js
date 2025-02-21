import React from 'react';

import { CreateConfigModal } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CreateConfigModal';
import { render, fireEvent } from 'test-utils';

jest.mock('react-dom', () => ({
  ...jest.requireActual('react-dom'),
  createPortal: (node) => node,
}));

describe('CreateConfigModal', () => {
  it('renders modal with default props', () => {
    const { getByText } = render(
      <CreateConfigModal isOpen={true} onClose={jest.fn()} onSave={jest.fn()} ctaText="Save" />,
    );
    expect(getByText('Create a custom configuration')).toBeInTheDocument();
    expect(getByText('Customise the payment blocks on your checkout')).toBeInTheDocument();
    expect(getByText('Configuration name')).toBeInTheDocument();
  });

  it('clicking Cancel button closes the modal', () => {
    const onClose = jest.fn();
    const { getByText } = render(
      <CreateConfigModal isOpen={true} onClose={onClose} onSave={jest.fn()} ctaText="Save" />,
    );
    fireEvent.click(getByText('Cancel'));
    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('clicking Create button with empty configId is disabled', () => {
    const { getByRole } = render(
      <CreateConfigModal isOpen={true} onClose={jest.fn()} onSave={jest.fn()} ctaText="Save" />,
    );
    const createButton = getByRole('button', { name: 'Save' });
    expect(createButton).toBeDisabled();
    fireEvent.click(createButton);
  });

  it('clicking Create button with non-empty configId calls onSave prop', () => {
    const onSave = jest.fn();
    const { getByText, getByLabelText } = render(
      <CreateConfigModal isOpen={true} onClose={jest.fn()} onSave={onSave} ctaText="Save" />,
    );
    const textInput = getByLabelText('Configuration name');
    fireEvent.change(textInput, { target: { value: 'test-config' } });
    const createButton = getByText('Save');
    fireEvent.click(createButton);
    expect(onSave).toHaveBeenCalledWith('test-config');
  });

  it('typing in TextInput updates configId state', () => {
    const { getByLabelText } = render(
      <CreateConfigModal isOpen={true} onClose={jest.fn()} onSave={jest.fn()} ctaText="Save" />,
    );
    const textInput = getByLabelText('Configuration name');
    fireEvent.change(textInput, { target: { value: 'test-config' } });
    expect(textInput.value).toBe('test-config');
  });
});
