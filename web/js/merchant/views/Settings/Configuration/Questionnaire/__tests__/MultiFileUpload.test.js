// utils
import { render, screen, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

// testable
import MultiFileUpload from 'merchant/views/Settings/Configuration/Questionnaire/MultiFileUpload';

// mocks
import * as Ajax from 'merchant/utils/ajax';
///- mocks

const merchantFetchSpyOn = jest.spyOn(Ajax, 'merchantFetch');

/**
 * Helper function to render component
 */
function renderComponent(props) {
  return render(
    <Provider
      store={storeWithInitialState({
        session: {
          mode: 'live',
          org: { id: '123' },
          user: {},
        },
      })}
    >
      <MultiFileUpload {...props} />
    </Provider>,
  );
}

let fileCounter = 0;
function createDummyFile() {
  const file = new File([`upload text file ${fileCounter}`], `test-${fileCounter++}.txt`, {
    type: 'text/plain',
  });
  return file;
}

/**
 * Helper functions to simulate file upload
 */
async function uploadFileOnInput(input) {
  const dummyFile = createDummyFile();
  await userEvent.upload(input, dummyFile);

  return dummyFile;
}

async function uploadMultipleFilesOnInput(input) {
  const dummyFiles = [createDummyFile(), createDummyFile()];
  await userEvent.upload(input, dummyFiles);

  return dummyFiles;
}

describe('Test <MultiFileUpload /> component', () => {
  test('Should render without breaking', () => {
    renderComponent();
    expect(screen.getByText('click to upload')).toBeInTheDocument();
  });

  test('Should upload single file', async () => {
    const onFileChange = jest.fn();

    renderComponent({
      onFileChange,
      name: 'file-uploader',
      label: 'Upload multiple files',
    });
    const fileUploadInput = screen.getByLabelText(/Drop file here or/i);
    const uploadedFile = await uploadFileOnInput(fileUploadInput);

    expect(onFileChange).toHaveBeenCalled();
    expect(fileUploadInput.files[0]).toBe(uploadedFile);
    expect(fileUploadInput.files).toHaveLength(1);
  });

  test('Should add and remove another file field to upload', async () => {
    const onFileChange = jest.fn();
    const onFileRemove = jest.fn();

    renderComponent({
      onFileChange,
      onFileRemove,
      name: 'file-uploader',
      label: 'Upload multiple files',
    });

    const addAnotherFileButton = screen.getByText(/Add another file/);

    await userEvent.click(addAnotherFileButton);
    expect(screen.getAllByText('click to upload')).toHaveLength(2);

    await userEvent.click(addAnotherFileButton);
    expect(screen.getAllByText('click to upload')).toHaveLength(3);

    await userEvent.click(addAnotherFileButton);
    expect(screen.getAllByText('click to upload')).toHaveLength(3);

    await userEvent.click(screen.getAllByTestId('btn-file-remove').at(0));
    expect(screen.getAllByText('click to upload')).toHaveLength(2);
    expect(onFileRemove).toHaveBeenCalled();

    await userEvent.click(screen.getAllByTestId('btn-file-remove').at(0));
    expect(screen.getAllByText('click to upload')).toHaveLength(1);
    expect(onFileRemove).toHaveBeenCalled();

    await userEvent.click(screen.getAllByTestId('btn-file-remove').at(0));
    expect(() => screen.getByTestId('btn-file-remove')).toThrow();
    expect(onFileRemove).toHaveBeenCalled();
  });

  test('Should required file', () => {
    const onFileChange = jest.fn();
    const onFileRemove = jest.fn();

    renderComponent({
      onFileChange,
      onFileRemove,
      required: true,
      name: 'file-uploader',
      label: 'Upload multiple files',
    });

    expect(screen.getByText('click to upload')).toBeInTheDocument();

    // should not render remove field button
    expect(() => screen.getByTestId('btn-file-remove')).toThrow();
  });

  test('Should accept only single file', async () => {
    const onFileChange = jest.fn();
    const onFileRemove = jest.fn();

    renderComponent({
      onFileChange,
      onFileRemove,
      name: 'file-uploader',
      label: 'Upload multiple files',
    });

    const fileUploadInput = screen.getByLabelText(/Drop file here or/);
    await uploadMultipleFilesOnInput(fileUploadInput);

    expect(fileUploadInput.files).toHaveLength(1);
  });

  test('Should upload and remove multiple files', async () => {
    const onFileChange = jest.fn();
    const onFileRemove = jest.fn();

    renderComponent({
      onFileChange,
      onFileRemove,
      name: 'file-uploader',
      label: 'Upload multiple files',
    });

    let fileUploadInput = screen.getByLabelText(/Drop file here or/);
    await uploadFileOnInput(fileUploadInput);

    const addAnotherFileButton = screen.getByText(/Add another file/);
    await userEvent.click(addAnotherFileButton);

    fileUploadInput = screen.getByLabelText(/Drop file here or/);
    await uploadFileOnInput(fileUploadInput);

    const dropzoneCloseBtn = screen.getAllByTestId('btn-dropzone-close');
    expect(dropzoneCloseBtn).toHaveLength(2);

    await userEvent.click(dropzoneCloseBtn.at(0));
    await userEvent.click(dropzoneCloseBtn.at(1));

    // file upload should be visible again
    expect(screen.getByText('click to upload')).toBeInTheDocument();
  });

  test('Should view the document', async () => {
    const onFileChange = jest.fn();

    global.open = jest.fn();

    merchantFetchSpyOn.mockImplementation(() =>
      Promise.resolve({ data: { url: 'http://localhost:8080' } }),
    );

    renderComponent({
      onFileChange,
      name: 'file-uploader',
      label: 'Upload multiple files',
      defaultFiles: [
        {
          id: `randomFileId`,
          display_name: 'Test-1',
        },
      ],
    });

    await userEvent.click(screen.getByText('Test-1'));

    expect(merchantFetchSpyOn).toHaveBeenCalled();
    expect(global.open).toHaveBeenCalled();
    expect(global.open).toHaveBeenCalledWith('http://localhost:8080');
  });
});
