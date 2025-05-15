import React from 'react';
import { screen, fireEvent } from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import DeactivateKeys from '../DeactivateKeys';
import { ApiKeyDelay } from 'apps/onboarding-experience/src/common/types/apiKeys';

describe('DeactivateKeys Component', () => {
  const mockHandleRegenerateApiKeys = jest.fn();
  const mockOnDismiss = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders modal with correct title and deactivation options', () => {
    renderWithWrappers(
      <DeactivateKeys
        handleRegenerateApiKeys={mockHandleRegenerateApiKeys}
        onDismiss={mockOnDismiss}
        isLoading={false}
      />,
    );
    expect(screen.getByText('Confirm and deactivate keys?')).toBeInTheDocument();
    expect(
      screen.getByText('Your current keys will be deactivated since you are generating new ones'),
    ).toBeInTheDocument();
    expect(screen.getByText('Select only one')).toBeInTheDocument();
    expect(screen.getByText('Deactivate old key immediately')).toBeInTheDocument();
    expect(screen.getByText('Deactivate old key in 24 hours')).toBeInTheDocument();
  });

  it('has Confirm button disabled until a deactivation option is selected', () => {
    renderWithWrappers(
      <DeactivateKeys
        handleRegenerateApiKeys={mockHandleRegenerateApiKeys}
        onDismiss={mockOnDismiss}
        isLoading={false}
      />,
    );

    expect(screen.getByRole('button', { name: 'Confirm' })).toBeDisabled();

    // Select an option
    fireEvent.click(screen.getByText('Deactivate old key immediately'));

    // Confirm button should now be enabled
    expect(screen.getByRole('button', { name: 'Confirm' })).not.toBeDisabled();
  });

  it('calls onDismiss when Cancel button is clicked', () => {
    renderWithWrappers(
      <DeactivateKeys
        handleRegenerateApiKeys={mockHandleRegenerateApiKeys}
        onDismiss={mockOnDismiss}
        isLoading={false}
      />,
    );

    fireEvent.click(screen.getByText('Cancel'));
    expect(mockOnDismiss).toHaveBeenCalledTimes(1);
  });

  it('calls handleRegenerateApiKeys with NO_DELAY when immediate option is selected', () => {
    renderWithWrappers(
      <DeactivateKeys
        handleRegenerateApiKeys={mockHandleRegenerateApiKeys}
        onDismiss={mockOnDismiss}
        isLoading={false}
      />,
    );

    // Select the immediate option
    fireEvent.click(screen.getByText('Deactivate old key immediately'));

    // Click Confirm
    fireEvent.click(screen.getByText('Confirm'));

    expect(mockHandleRegenerateApiKeys).toHaveBeenCalledWith(ApiKeyDelay.NO_DELAY);
  });

  it('calls handleRegenerateApiKeys with DELAY when 24-hour option is selected', () => {
    renderWithWrappers(
      <DeactivateKeys
        handleRegenerateApiKeys={mockHandleRegenerateApiKeys}
        onDismiss={mockOnDismiss}
        isLoading={false}
      />,
    );

    // Select the 24-hour delay option
    fireEvent.click(screen.getByText('Deactivate old key in 24 hours'));

    // Click Confirm
    fireEvent.click(screen.getByText('Confirm'));

    expect(mockHandleRegenerateApiKeys).toHaveBeenCalledWith(ApiKeyDelay.DELAY);
  });
});
