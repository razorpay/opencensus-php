import React from 'react';

import EditReport from 'merchant/views/Reconciliations/Dashboard/EditReport';
import { render, screen, waitFor } from 'test-utils';

const mockShowNotification = jest.fn();

const editReportProps = {
  showNotification: mockShowNotification,
};

const renderEditReport = (props) => {
  return render(<EditReport {...props} />);
};

describe('EditReport Component', () => {
  test('Should render the component without throwing errors', async () => {
    await waitFor(() => {
      expect(() => renderEditReport(editReportProps)).not.toThrow();
    });
  });

  test('renders Edit Report heading', async () => {
    renderEditReport(editReportProps);

    await waitFor(() => {
      expect(screen.getByText(/edit report/i)).toBeInTheDocument();
    });
  });

  test('renders close button', async () => {
    renderEditReport(editReportProps);

    await waitFor(() => {
      expect(screen.getByLabelText(/close button/i)).toBeInTheDocument();
    });
  });
});
