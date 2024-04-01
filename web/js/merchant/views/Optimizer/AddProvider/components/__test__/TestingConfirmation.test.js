import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

import { TestingConfirmation } from '../TestingConfirmation';

describe('Add Provider > TestingConfirmation', () => {
  const mockProps = {
    isModalOpen: true,
    startIntegrationTesting: jest.fn(),
    raiseTicket: jest.fn(),
  };

  const App = (props) => {
    return (
      <BladeProvider themeTokens={bladeTheme}>
        <TestingConfirmation {...props} />
      </BladeProvider>
    );
  };

  it('should render TestingConfirmation without any errors', () => {
    expect(() => render(<App {...mockProps} />)).not.toThrowError();
  });

  it('should render the correct elements', async () => {
    render(<App {...mockProps} />);
    expect(screen.getByText('Optimizer Testing')).toBeInTheDocument();
    expect(screen.getByText('You can test optimizer integration in two ways:')).toBeInTheDocument();
    expect(screen.getByText('1. Test it yourself')).toBeInTheDocument();
    expect(screen.getByText('2. Raise a support ticket')).toBeInTheDocument();
    expect(screen.getByText('Raise a ticket')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Raise a ticket'));
    expect(mockProps.raiseTicket).toHaveBeenCalled();
    expect(screen.getByText('Test now')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Test now'));
    expect(mockProps.startIntegrationTesting).toHaveBeenCalled();
  });
});
