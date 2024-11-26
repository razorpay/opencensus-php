import React from 'react';

import CreateReport from 'merchant/views/Reconciliations/Dashboard/CreateReport';
import { render, screen, waitFor } from 'test-utils';

const createReportProps = {
  showNotification: jest.fn(),
};

const renderCreateReport = (props) => {
  return render(<CreateReport {...props} />);
};

describe('CreateReport Component', () => {
  test('Should render the component without throwing errors', async () => {
    await waitFor(() => {
      expect(() => renderCreateReport(createReportProps)).not.toThrow();
    });
  });

  test('render report template accordion', async () => {
    renderCreateReport(createReportProps);

    await waitFor(() => {
      expect(screen.getByText(/report template/i)).toBeInTheDocument();
    });
  });

  test('renders Create Report heading', async () => {
    renderCreateReport(createReportProps);

    await waitFor(() => {
      expect(screen.getByText(/create report/i)).toBeInTheDocument();
    });
  });

  test('renders source data accordion', async () => {
    renderCreateReport(createReportProps);

    await waitFor(() => {
      expect(screen.getByText(/select source data and format/i)).toBeInTheDocument();
    });
  });

  test('render Create Report close button', async () => {
    renderCreateReport(createReportProps);

    await waitFor(() => {
      expect(screen.getByLabelText(/close button/i)).toBeInTheDocument();
    });
  });
});
