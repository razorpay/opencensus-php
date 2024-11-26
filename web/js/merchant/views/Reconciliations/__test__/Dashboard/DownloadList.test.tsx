import React from 'react';

import DownloadList from 'merchant/views/Reconciliations/Dashboard/DownloadList';
import { render, screen, waitFor } from 'test-utils';

const renderDownloadList = () => {
  return render(<DownloadList />);
};

describe('DownloadList Component', () => {
  test('Should render the component without throwing errors', async () => {
    await waitFor(() => {
      expect(() => renderDownloadList()).not.toThrow();
    });
  });

  test('renders download reports heading', async () => {
    renderDownloadList();

    await waitFor(() => {
      expect(screen.getByText(/download reports/i)).toBeInTheDocument();
    });
  });
});
