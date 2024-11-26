import React from 'react';

import SaveConfigModal from 'merchant/views/Reconciliations/Dashboard/SaveConfigModal';
import { render, waitFor, fireEvent, screen } from 'test-utils';

const mockSetIsOpenSaveConfigModal = jest.fn();
const mockSetConfigForm = jest.fn();
const mockSetHasCompletedCreateReportStep = jest.fn();

const saveConfigModalProps = {
  isOpenSaveConfigModal: true,
  setIsOpenSaveConfigModal: mockSetIsOpenSaveConfigModal,
  configForm: { name: '', description: '' },
  setConfigForm: mockSetConfigForm,
  setHasCompletedCreateReportStep: mockSetHasCompletedCreateReportStep,
};

const renderSaveConfigModal = (props) => {
  return render(<SaveConfigModal {...props} />);
};

describe('SaveConfigModal', () => {
  test('Should render the component without throwing errors', async () => {
    await waitFor(() => {
      expect(() => renderSaveConfigModal(saveConfigModalProps)).not.toThrow();
    });
  });

  test('should render the Save Config modal and display the correct title', async () => {
    renderSaveConfigModal(saveConfigModalProps);
    await waitFor(() => {
      expect(screen.getByText(/save your report template/i)).toBeInTheDocument();
    });
  });

  test('should update the report name on input change', async () => {
    renderSaveConfigModal(saveConfigModalProps);

    const reportNameInput = screen.getByLabelText(/report name/i);
    fireEvent.change(reportNameInput, { target: { value: 'New Report Name' } });

    await waitFor(() => {
      expect(mockSetConfigForm).toHaveBeenCalledWith(expect.any(Function));
      expect(mockSetConfigForm).toHaveBeenCalledTimes(1);
    });
  });

  test('should update the description on input change', async () => {
    renderSaveConfigModal(saveConfigModalProps);

    const descriptionInput = screen.getByLabelText(/description/i);
    fireEvent.change(descriptionInput, { target: { value: 'This is a report description.' } });

    await waitFor(() => {
      expect(mockSetConfigForm).toHaveBeenCalledWith(expect.any(Function));
      expect(mockSetConfigForm).toHaveBeenCalledTimes(1);
    });
  });

  test('should dismiss the modal on Go Back button click', async () => {
    renderSaveConfigModal(saveConfigModalProps);

    const goBackButton = screen.getByRole('button', { name: /go back/i });
    fireEvent.click(goBackButton);

    await waitFor(() => {
      expect(mockSetIsOpenSaveConfigModal).toHaveBeenCalledWith(false);
    });
  });

  test('should proceed with confirming the report when report name is filled', async () => {
    const filledSaveConfigModalProps = {
      ...saveConfigModalProps,
      configForm: { name: 'Report Name', description: 'Description' },
    };

    renderSaveConfigModal(filledSaveConfigModalProps);

    const confirmButton = screen.getByRole('button', { name: /confirm/i });
    fireEvent.click(confirmButton);

    await waitFor(() => {
      expect(mockSetHasCompletedCreateReportStep).toHaveBeenCalledWith(expect.any(Function));
      expect(mockSetIsOpenSaveConfigModal).toHaveBeenCalledWith(false);
    });
  });
});
