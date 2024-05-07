import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

import { GoLiveConfirmation } from 'merchant/views/Optimizer/AddProvider/components/GoLiveConfirmation';

describe('Optimizer IntegrationTesting GoLiveConfirmation', () => {
  const mockProps = {
    isModalOpen: true,
    closeGoLiveConfirmationModal: jest.fn(),
    takeProviderLive: jest.fn(),
    isUpdatingProvider: false,
  };

  const App = (props) => {
    return (
      <BladeProvider themeTokens={bladeTheme}>
        <GoLiveConfirmation {...props} />
      </BladeProvider>
    );
  };

  it('should render GoLiveConfirmation without any errors', () => {
    expect(() => render(<App {...mockProps} />)).not.toThrowError();
  });

  it('should render the correct text', async () => {
    render(<App {...mockProps} />);
    expect(
      screen.getByText(
        'We discovered issues in your integration audit. Are you sure you want to override and go live?',
      ),
    ).toBeInTheDocument();
    const cancelButton = screen.getByRole('button', { name: 'Cancel' });
    expect(cancelButton).toBeInTheDocument();
    expect(cancelButton).not.toBeDisabled();
    await userEvent.click(cancelButton);
    expect(mockProps.closeGoLiveConfirmationModal).toHaveBeenCalled();
    const yesButton = screen.getByRole('button', { name: 'Yes' });
    expect(yesButton).toBeInTheDocument();
    expect(yesButton).not.toBeDisabled();
    await userEvent.click(yesButton);
    expect(mockProps.takeProviderLive).toHaveBeenCalled();
  });
});
