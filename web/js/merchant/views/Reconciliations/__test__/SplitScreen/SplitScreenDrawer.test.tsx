import React from 'react';
import { render, screen, userEvent, act, waitFor } from 'test-utils';
import SplitScreenDrawer from 'merchant/views/Reconciliations/SplitScreen/SplitScreenDrawer';

const mockSetIsDrawerOpen = jest.fn();
const mockRecordId = '123';

const SplitScreenDrawerProps = {
  isDrawerOpen: true,
  setIsDrawerOpen: mockSetIsDrawerOpen,
  recordId: mockRecordId,
};

const renderSplitScreenDrawer = (props) => {
  return render(<SplitScreenDrawer {...props} />);
};

describe('SplitScreenDrawer', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('should render the SplitScreenDrawer without throwing errors', async () => {
    await waitFor(() => {
      expect(() => renderSplitScreenDrawer(SplitScreenDrawerProps)).not.toThrow();
    });
  });

  test('should render the drawer title', async () => {
    renderSplitScreenDrawer(SplitScreenDrawerProps);
    expect(screen.getByText(/reconciled data/i)).toBeInTheDocument();
  });

  test('should call setIsDrawerOpen when the drawer is closed', async () => {
    renderSplitScreenDrawer(SplitScreenDrawerProps);
    const closeButton = await screen.findByRole('button', { name: /close/i });

    expect(closeButton).toBeInTheDocument();

    await act(async () => {
      await userEvent.click(closeButton);
    });

    expect(mockSetIsDrawerOpen).toHaveBeenCalledWith(false);
    expect(mockSetIsDrawerOpen).toHaveBeenCalledTimes(1);
  });

  test('should not render the table when drawer is closed', async () => {
    renderSplitScreenDrawer({
      ...SplitScreenDrawerProps,
      isDrawerOpen: false,
    });

    expect(screen.queryByRole('table')).not.toBeInTheDocument();
  });
});
