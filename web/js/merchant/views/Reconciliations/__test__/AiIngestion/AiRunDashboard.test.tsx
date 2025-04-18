import React from 'react';
import { render, screen } from 'test-utils';

import AiRunDashboard from 'merchant/views/Reconciliations/AiIngestion/AiRunDashboard';

const defaultProps = {
  mlConfigIds: { auditLogId: '12345' },
  setIsGoBackClicked: jest.fn(),
  setAiIngestionStages: jest.fn(),
};

const renderAiRunDashboard = (props = defaultProps) => {
  return render(<AiRunDashboard {...props} />);
};

describe('AiRunDashboard Component', () => {
  test('renders without errors', () => {
    expect(() => renderAiRunDashboard()).not.toThrow();
  });

  test('should displays the initial reconciliation message', () => {
    renderAiRunDashboard();
    expect(
      screen.getByText(/your first reconciliation run may take a few moments/i),
    ).toBeInTheDocument();
  });

  test('disables the download button initially', () => {
    renderAiRunDashboard();

    const downloadButton = screen.getByRole('button', { name: /download recon report/i });

    expect(downloadButton).toBeDisabled();
  });

  test('back and save buttons are disabled initially', () => {
    renderAiRunDashboard();

    const backButton = screen.getByRole('button', { name: /go back/i });
    const saveButton = screen.getByRole('button', { name: /save/i });

    expect(backButton).toBeDisabled();
    expect(saveButton).toBeDisabled();
  });
});
