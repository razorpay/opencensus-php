import React from 'react';
import { render, screen, userEvent } from 'test-utils';

import EditProcessName from 'merchant/views/Reconciliations/AiIngestion/EditProcessName';

const editProcessNameProps = {
  isEditingProcessName: false,
  processName: 'Test Process',
  aiIngestionStages: { 'Add Sources': false, Mapping: false, Processing: false },
  setProcessName: jest.fn(),
  setIsEditingProcessName: jest.fn(),
};

const renderEditProcessName = (props) => {
  return render(<EditProcessName {...props} />);
};

describe('EditProcessName Component', () => {
  test('renders without errors', () => {
    expect(() => renderEditProcessName(editProcessNameProps)).not.toThrow();
  });

  test('displays the process name correctly', () => {
    renderEditProcessName(editProcessNameProps);
    expect(screen.getByText(/test process/i)).toBeInTheDocument(); // Case-insensitive match
  });

  test('allows editing when Add Sources stage is not complete', async () => {
    renderEditProcessName(editProcessNameProps);

    const editButton = screen.getByLabelText(/edit/i);
    expect(editButton).toBeInTheDocument();
    await userEvent.click(editButton);

    expect(editProcessNameProps.setIsEditingProcessName).toHaveBeenCalledWith(true);
  });

  test('does not show edit button when Add Sources stage is complete', () => {
    renderEditProcessName({ ...editProcessNameProps, aiIngestionStages: { 'Add Sources': true } });
    expect(screen.queryByLabelText(/edit/i)).not.toBeInTheDocument();
  });

  test('renders input field when editing', () => {
    renderEditProcessName({ ...editProcessNameProps, isEditingProcessName: true });

    expect(screen.getByRole('textbox', { name: /name for the process/i })).toBeInTheDocument();
  });

  test('saves the new name and exits edit mode when clicking Save', async () => {
    renderEditProcessName({ ...editProcessNameProps, isEditingProcessName: true });

    const saveButton = screen.getByText(/save/i);
    await userEvent.click(saveButton);

    expect(editProcessNameProps.setIsEditingProcessName).toHaveBeenCalledWith(false);
  });
});
