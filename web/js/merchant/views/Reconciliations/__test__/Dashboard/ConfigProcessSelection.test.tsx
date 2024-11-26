import React from 'react';
import { render, screen, waitFor, fireEvent } from 'test-utils';

import ConfigProcessSelection from 'merchant/views/Reconciliations/Dashboard/ConfigProcessSelection';

const mockSelectedProcessesItem = [
  { id: '1', name: 'Process 1', merchant_sources: [{ name: 'Source A' }, { name: 'Source B' }] },
  { id: '2', name: 'Process 2', merchant_sources: [{ name: 'Source C' }] },
];

const configProcessSelectionProps = {
  selectedProcessesItem: mockSelectedProcessesItem,
  isProcessesSelectionDone: false,
  setOpenProcessSelectionModal: jest.fn(),
  removeProcessCardHandler: jest.fn(),
  setHasCompletedCreateReportStep: jest.fn(),
};

const renderConfigProcessSelection = (props) => {
  return render(<ConfigProcessSelection {...props} />);
};

describe('ConfigProcessSelection Component', () => {
  test('should render the component without throwing errors', async () => {
    await waitFor(() => {
      expect(() => renderConfigProcessSelection(configProcessSelectionProps)).not.toThrow();
    });
  });

  test('should render "Add Process" card when selection is not done', async () => {
    renderConfigProcessSelection(configProcessSelectionProps);

    await waitFor(() => {
      expect(screen.getByText(/add process/i)).toBeInTheDocument();
      expect(screen.getByText(/select the source data you want/i)).toBeInTheDocument();
    });
  });

  test('should call setHasCompletedCreateReportStep when Confirm button is clicked', async () => {
    renderConfigProcessSelection(configProcessSelectionProps);

    const confirmButton = screen.getByRole('button', { name: /confirm/i });
    fireEvent.click(confirmButton);

    await waitFor(() => {
      expect(configProcessSelectionProps.setHasCompletedCreateReportStep).toHaveBeenCalledWith(
        expect.any(Function),
      );
    });
  });

  test('should disable Confirm button if no processes are selected', () => {
    const newProps = { ...configProcessSelectionProps, selectedProcessesItem: [] };
    renderConfigProcessSelection(newProps);

    const confirmButton = screen.getByRole('button', { name: /confirm/i });
    expect(confirmButton).toBeDisabled();
  });
});
