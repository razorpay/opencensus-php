import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, userEvent } from 'common/services/test/test-utils';

import MultiFileUpload from 'merchant/views/PartnerDashboard/SubMerchant/components/MultiFileUpload';

function createDummyFile() {
  const file = new File([''], 'example.pdf', { type: 'application/pdf' });
  return file;
}

/**
 * Helper functions to simulate file upload
 */
const uploadFileOnInput = async (input: HTMLInputElement): Promise<File> => {
  const dummyFile = createDummyFile();
  await userEvent.upload(input, dummyFile);

  return dummyFile;
};

const onFileChangeMock = jest.fn(() => Promise.resolve({ store_id: 'test_id', status: 'success' }));
const onFileRemoveMock = jest.fn();

describe('MultiFileUpload', () => {
  const renderApp = () => {
    render(
      <MultiFileUpload
        name="bank_statement"
        label=""
        onFileChange={onFileChangeMock}
        onFileRemove={onFileRemoveMock}
      />,
    );
  };

  test('Should render without breaking', () => {
    renderApp();
    expect(screen.getByText('click to upload')).toBeInTheDocument();
  });

  test('Should upload single file', async () => {
    renderApp();
    const fileUploadInput = screen.getByLabelText(/Drop file here or/i) as HTMLInputElement;
    await uploadFileOnInput(fileUploadInput);

    expect(onFileChangeMock).toHaveBeenCalled();
    expect(fileUploadInput.files).toHaveLength(1);
  });

  test('Should add and remove another file field to upload', async () => {
    renderApp();

    const addAnotherFileButton = screen.getByText(/Add another file/);

    await userEvent.click(addAnotherFileButton);
    expect(screen.getAllByText('click to upload')).toHaveLength(2);

    await userEvent.click(addAnotherFileButton);
    expect(screen.getAllByText('click to upload')).toHaveLength(3);

    const fileRemoveButton = screen.getAllByTestId('btn-file-remove');
    await userEvent.click(fileRemoveButton[0]);
    expect(screen.getAllByText('click to upload')).toHaveLength(2);
    expect(onFileRemoveMock).toHaveBeenCalled();

    const fileRemoveButton1 = screen.getAllByTestId('btn-file-remove');
    await userEvent.click(fileRemoveButton1[0]);
    expect(screen.getAllByText('click to upload')).toHaveLength(1);
    expect(onFileRemoveMock).toHaveBeenCalled();
  });
});
