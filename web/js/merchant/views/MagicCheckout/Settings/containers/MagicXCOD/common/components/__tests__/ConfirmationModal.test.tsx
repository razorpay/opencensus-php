import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';

import { BladeProvider, Button } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';

import {
  ConfirmationModalProvider,
  useConfirm,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/common/components/ConfirmationModal';

const mockFn = jest.fn();

const TestComponent: React.FC = () => {
  const confirm = useConfirm();

  const handleClick = async () => {
    const didConfirm = await confirm({
      title: 'Please confirm',
      description: 'Click on Yes to confirm or No to dismiss',
      confirmText: 'Yes',
      dismissText: 'No',
    });

    if (didConfirm) {
      mockFn('Confirmed');
    } else {
      mockFn('Dismissed');
    }
  };

  return <Button onClick={handleClick}>Confirm</Button>;
};

describe('ConfirmationModal', () => {
  afterEach(() => {
    jest.clearAllMocks();
  });

  const App = () => {
    return (
      <BladeProvider themeTokens={bladeTheme}>
        <ConfirmationModalProvider>
          <TestComponent />
        </ConfirmationModalProvider>
      </BladeProvider>
    );
  };

  test('resolves promise with true when confirm button is clicked', async () => {
    render(<App />);

    act(() => {
      fireEvent.click(screen.getByText(/confirm/i));
    });
    expect(screen.getByText(/please confirm/i)).toBeInTheDocument();
    act(() => {
      fireEvent.click(screen.getByRole('button', { name: /yes/i }));
    });

    await waitFor(() => {
      expect(screen.queryByText(/please confirm/i)).not.toBeInTheDocument();
      expect(mockFn).toHaveBeenCalledWith('Confirmed');
    });
  });

  test('resolves promise with false when dismiss button is clicked', async () => {
    render(<App />);

    act(() => {
      fireEvent.click(screen.getByText(/confirm/i));
    });
    expect(screen.getByText(/please confirm/i)).toBeInTheDocument();
    act(() => {
      fireEvent.click(screen.getByRole('button', { name: /no/i }));
    });

    await waitFor(() => {
      expect(screen.queryByText(/please confirm/i)).not.toBeInTheDocument();
      expect(mockFn).toHaveBeenCalledWith('Dismissed');
    });
  });
});
