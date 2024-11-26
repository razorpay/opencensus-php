import React from 'react';
import { render, screen, waitFor } from 'test-utils';

import ReportList from 'merchant/views/Reconciliations/Dashboard/ReportList';

const renderReportList = () => {
  return render(<ReportList />);
};

beforeEach(() => {
  jest.clearAllMocks();
});

describe('ReportList Component', () => {
  test('Should render the component without throwing errors', async () => {
    await waitFor(() => {
      expect(() => renderReportList()).not.toThrow();
    });
  });

  test('renders report configs heading', async () => {
    renderReportList();

    await waitFor(() => {
      expect(screen.getByText(/configs/i)).toBeInTheDocument();
    });
  });

  test('renders Create New button', async () => {
    renderReportList();

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /create new/i })).toBeInTheDocument();
    });
  });
});
