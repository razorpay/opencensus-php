import React from 'react';
import { render, waitFor, fireEvent, screen } from 'test-utils';
import 'merchant_common/views/Reports/mocks/hooks/useReportsSplitzExperimentsMock';
import DownloadReportModal from 'merchant/views/Reconciliations/Dashboard/DownloadReportModal';

const mockSetIsOpenDownloadModal = jest.fn();
const mockDownloadReportConfig = { id: '123' };

const downloadReportModalProps = {
  downloadReportConfig: mockDownloadReportConfig,
  isOpenDownloadModal: true,
  setIsOpenDownloadModal: mockSetIsOpenDownloadModal,
};

const renderDownloadReportModal = (props) => {
  return render(<DownloadReportModal {...props} />);
};

describe('DownloadReportModal', () => {
  test('Should render the component without throwing errors', async () => {
    await waitFor(() => {
      expect(() => renderDownloadReportModal(downloadReportModalProps)).not.toThrow();
    });
  });

  test('should render the Download Report modal and display the correct title', async () => {
    renderDownloadReportModal(downloadReportModalProps);
    await waitFor(() => {
      expect(screen.getByText(/download report/i)).toBeInTheDocument();
    });
  });

  test('should update the file name on input change', async () => {
    renderDownloadReportModal(downloadReportModalProps);

    const fileNameInput = screen.getByLabelText(/file name/i);
    fireEvent.change(fileNameInput, { target: { value: 'My Report' } });

    await waitFor(() => {
      expect(screen.getByDisplayValue('My Report')).toBeInTheDocument();
    });
  });

  test('should select the file format on radio button change', async () => {
    renderDownloadReportModal(downloadReportModalProps);

    const excelRadio = screen.getByLabelText(/excel/i);
    fireEvent.click(excelRadio);

    await waitFor(() => {
      expect(screen.getByLabelText<HTMLInputElement>(/excel/i).checked).toBe(true);
    });
  });

  test('should update the recipient email on input change', async () => {
    renderDownloadReportModal(downloadReportModalProps);

    const emailInput = screen.getByLabelText(/add recipient's details/i);
    fireEvent.change(emailInput, { target: { value: 'test@example.com' } });

    await waitFor(() => {
      expect(screen.getByDisplayValue('test@example.com')).toBeInTheDocument();
    });
  });

  test('should dismiss the modal on Cancel button click', async () => {
    renderDownloadReportModal(downloadReportModalProps);

    const cancelButton = screen.getByRole('button', { name: /cancel/i });
    fireEvent.click(cancelButton);

    await waitFor(() => {
      expect(mockSetIsOpenDownloadModal).toHaveBeenCalledWith(false);
    });
  });
});
