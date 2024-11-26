import React from 'react';

import EditColumnModal from 'merchant/views/Reconciliations/Dashboard/EditColumnModal';
import { render, waitFor, fireEvent, screen } from 'test-utils';

const mockSetOpenEditNameModal = jest.fn();
const mockSetUpdatedNameForColumn = jest.fn();
const mockRenameSelectedColumnForReport = jest.fn();

const editColumnModalProps = {
  isOpenEditNameModal: true,
  setOpenEditNameModal: mockSetOpenEditNameModal,
  editColumn: { id: '123', name: 'Old Name', editedName: 'Old Edited Name' },
  updatedNameForColumn: 'Updated Name',
  setUpdatedNameForColumn: mockSetUpdatedNameForColumn,
  renameSelectedColumnForReport: mockRenameSelectedColumnForReport,
};

const renderEditColumnModal = (props) => {
  return render(<EditColumnModal {...props} />);
};

describe('EditColumnModal', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('Should render the component without throwing errors', async () => {
    await waitFor(() => {
      expect(() => renderEditColumnModal(editColumnModalProps)).not.toThrow();
    });
  });

  test('should render the Edit Column modal and display the correct title', async () => {
    renderEditColumnModal(editColumnModalProps);
    await waitFor(() => {
      expect(screen.getByText(/edit column/i)).toBeInTheDocument();
    });
  });

  test('should display the current column name and allow updating new name', async () => {
    renderEditColumnModal(editColumnModalProps);

    const currentColumnNameInput = screen.getByLabelText(/current column name/i);
    expect(currentColumnNameInput).toHaveValue('Old Edited Name'); // Check the current name

    const updatedNameInput = screen.getByLabelText(/updated name/i);
    fireEvent.change(updatedNameInput, { target: { value: 'New Column Name' } });

    await waitFor(() => {
      expect(mockSetUpdatedNameForColumn).toHaveBeenCalledWith('New Column Name');
    });
  });
});
