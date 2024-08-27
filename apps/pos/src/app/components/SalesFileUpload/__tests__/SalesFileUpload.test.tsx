import React from 'react';
import SalesFileUpload from '../SalesFileUpload';
import { formatBytes, processFilesForModularSave } from '../helper';
import { downloadFileHandler, uploadFileToUFHHandler } from './mocks/handlers';
import {
  render,
  screen,
  server,
  userEvent,
  waitForElementToBeRemoved,
  waitFor,
} from 'apps/pos/src/services/test/test-utils';

jest.mock('shell/commonStore', () => ({
  ...jest.requireActual('shell/commonStore'),
  getUser: () => ({
    user: {
      id: 'test_user_id',
    },
  }),
}));

const initProps = {
  merchantId: 'test_merchant_id',
  name: 'test_file',
  label: 'Test File Upload',
  accept: '.pdf',
  uploadType: 'single' as const,
  error: '',
  isLoading: false,
  onChange: jest.fn(),
  onRemove: (_id, callback) => {
    callback();
  },
  onError: jest.fn(),
  maxLimit: 2,
  maxSize: 2 * 1024 * 1024,
};

const renderApp = (defaultProps = {}) => {
  const props = {
    ...initProps,
    ...defaultProps,
  };
  render(<SalesFileUpload {...props} />);
};

describe('<SalesFileUpload/>', () => {
  test('should render file upload input with label', () => {
    renderApp();
    expect(screen.getByText('Test File Upload')).toBeInTheDocument();
    expect(screen.getByText('Choose Files')).toBeInTheDocument();
  });

  test('should render uploaded file with onChange trigger and remove input if type is single', async () => {
    server.use(uploadFileToUFHHandler.success());
    renderApp();
    const mockFile = new File(['hello'], 'hello.pdf', { type: 'pdf' });
    const inputComponent = screen.getByLabelText('file-upload-input');
    await userEvent.upload(inputComponent, mockFile);
    await waitForElementToBeRemoved(() => screen.getByLabelText('file-upload-loader'));
    expect(screen.getByText('hello.pdf')).toBeInTheDocument();
    const uploadedFile = {
      fileStoreId: 'store_id_123',
      name: 'hello.pdf',
      size: 5,
    };
    expect(initProps.onChange).toHaveBeenCalledWith([uploadedFile]);
    expect(screen.queryByText('Choose Files')).toBeNull();
  });

  test('should render uploaded files with onChange trigger  and should not remove input if type is multiple', async () => {
    server.use(uploadFileToUFHHandler.success());
    const defaultValue = [
      {
        fileStoreId: 'random_file_123',
        name: 'defaultFile.pdf',
        size: 500000,
      },
    ];
    renderApp({ uploadType: 'multiple', defaultValue });
    const mockFile = new File(['hello'], 'hello.pdf', { type: 'pdf' });
    const inputComponent = screen.getByLabelText('file-upload-input');
    await userEvent.upload(inputComponent, mockFile);
    await waitForElementToBeRemoved(() => screen.getByLabelText('file-upload-loader'));
    expect(screen.getByText('hello.pdf')).toBeInTheDocument();
    const uploadedFile = [
      {
        fileStoreId: 'store_id_123',
        name: 'hello.pdf',
        size: 5,
      },
    ];
    expect(initProps.onChange).toHaveBeenCalledWith([...defaultValue, ...uploadedFile]);
    expect(screen.getByText('Choose Files')).toBeInTheDocument();
  });

  test('should show error while trying to upload more than maxFiles', async () => {
    server.use(uploadFileToUFHHandler.success());
    const defaultValue = [
      {
        fileStoreId: 'random_file_123',
        name: 'defaultFile.pdf',
        size: 500000,
      },
      {
        fileStoreId: 'random_file_345',
        name: 'defaultFile1.pdf',
        size: 500000,
      },
    ];
    renderApp({ uploadType: 'multiple', defaultValue });
    const mockFile = new File(['hello'], 'hello.pdf', { type: 'pdf' });
    const inputComponent = screen.getByLabelText('file-upload-input');
    await userEvent.upload(inputComponent, mockFile);
    await waitFor(() => {
      expect(screen.getByText('Cannot upload more than 2 files!')).toBeVisible();
    });
  });

  test('should show error if passed from parent', () => {
    renderApp({ error: 'Some error' });
    expect(screen.getByText('Some error')).toBeInTheDocument();
  });

  test('should show error when ufh API fails', async () => {
    server.use(uploadFileToUFHHandler.failure());
    renderApp();
    const mockFile = new File(['hello'], 'hello.pdf', { type: 'pdf' });
    expect(screen.getByText('Choose Files')).toBeInTheDocument();
    const inputComponent = screen.getByLabelText('file-upload-input');
    await userEvent.upload(inputComponent, mockFile);
    await waitForElementToBeRemoved(() => screen.getByLabelText('file-upload-loader'));
    expect(screen.getByText('Some error occurred while uploading file!')).toBeInTheDocument();
  });

  test('should show error when ufh API fails', async () => {
    server.use(uploadFileToUFHHandler.failure());
    renderApp();
    const mockFile = new File(['hello'], 'hello.pdf', { type: 'pdf' });
    expect(screen.getByText('Choose Files')).toBeInTheDocument();
    const inputComponent = screen.getByLabelText('file-upload-input');
    await userEvent.upload(inputComponent, mockFile);
    await waitForElementToBeRemoved(() => screen.getByLabelText('file-upload-loader'));
    expect(screen.getByText('Some error occurred while uploading file!')).toBeInTheDocument();
  });

  test('should show error when file size exceeds the limit', async () => {
    renderApp({ maxSize: 1 });
    const mockFile = new File(['hello'], 'hello.pdf', { type: 'pdf' });
    const inputComponent = screen.getByLabelText('file-upload-input');
    await userEvent.upload(inputComponent, mockFile);
    await waitFor(() => {
      expect(screen.getByText('File size should not exceed 1 Bytes!')).toBeInTheDocument();
    });
  });

  test('should trgger remove callback when clicked on remove icon', async () => {
    server.use(uploadFileToUFHHandler.success());
    const defaultValue = [
      {
        fileStoreId: 'random_file_123',
        name: 'defaultFile.pdf',
        size: 500000,
      },
    ];
    renderApp({ uploadType: 'multiple', defaultValue });
    expect(screen.getByText('defaultFile.pdf')).toBeInTheDocument();
    await userEvent.click(screen.getByLabelText('delete-file'));
    await waitFor(() => {
      expect(screen.queryByText('defaultFile.pdf')).toBeNull();
    });
  });

  test('should trgger remove callback when clicked on remove icon', async () => {
    server.use(uploadFileToUFHHandler.success());
    const defaultValue = [
      {
        fileStoreId: 'random_file_123',
        name: 'defaultFile.pdf',
        size: 500000,
      },
    ];
    renderApp({ uploadType: 'multiple', defaultValue });
    expect(screen.getByText('defaultFile.pdf')).toBeInTheDocument();
    await userEvent.click(screen.getByLabelText('delete-file'));
    await waitFor(() => {
      expect(screen.queryByText('defaultFile.pdf')).toBeNull();
    });
  });

  test('should download file when clicked on download icon', async () => {
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    const windowOpenSpy = jest.spyOn(window, 'open').mockImplementation(() => null);
    server.use(uploadFileToUFHHandler.success(), downloadFileHandler.success());
    renderApp();
    const mockFile = new File(['hello'], 'hello.pdf', { type: 'pdf' });
    const inputComponent = screen.getByLabelText('file-upload-input');
    await userEvent.upload(inputComponent, mockFile);
    await waitForElementToBeRemoved(() => screen.getByLabelText('file-upload-loader'));
    expect(screen.getByText('hello.pdf')).toBeInTheDocument();
    await userEvent.click(screen.getByLabelText('download-file'));
    await waitFor(() => {
      expect(windowOpenSpy).toHaveBeenCalledWith('www.someRandomDownloadurl.com', '_blank');
    });
    windowOpenSpy.mockRestore();
  });

  test('should show error when download API fails', async () => {
    server.use(uploadFileToUFHHandler.success(), downloadFileHandler.failure());
    renderApp();
    const mockFile = new File(['hello'], 'hello.pdf', { type: 'pdf' });
    const inputComponent = screen.getByLabelText('file-upload-input');
    await userEvent.upload(inputComponent, mockFile);
    await waitForElementToBeRemoved(() => screen.getByLabelText('file-upload-loader'));
    expect(screen.getByText('hello.pdf')).toBeInTheDocument();
    await userEvent.click(screen.getByLabelText('download-file'));
    await waitFor(() => {
      expect(screen.getByText('Some error occurred while downloading file!')).toBeInTheDocument();
    });
  });
});

describe('formatBytes', () => {
  test('should return formatted bytes', () => {
    const string = formatBytes(5 * 1024 * 1024);
    expect(string).toBe('5 MB');
  });

  test('should return formatted bytes', () => {
    const string = formatBytes(5);
    expect(string).toBe('5 Bytes');
  });

  test('should return formatted bytes', () => {
    const string = formatBytes(5 * 1024);
    expect(string).toBe('5 KB');
  });
});

describe('processFilesForModularSave', () => {
  test('should return empty array if no files', () => {
    const result = processFilesForModularSave([]);
    expect(result).toEqual([]);
  });

  test('should return correct array if files', () => {
    const dummyfile = [
      {
        fileStoreId: 'random_file_123',
        name: 'defaultFile.pdf',
        size: 500000,
      },
    ];
    const result = processFilesForModularSave(dummyfile);
    expect(result).toEqual([
      {
        file_id: 'random_file_123-sales-file',
        file_store_id: 'random_file_123',
        name: 'defaultFile.pdf',
        size: 500000,
      },
    ]);
  });
});
