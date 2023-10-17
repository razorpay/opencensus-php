import {
  fileObject,
  dateObject,
} from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/__test__/fixtures';
import 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/__test__/fixtures/mocks';
import File from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/DownloadPopup/File';
import {
  FileName,
  FileStatus,
  FileType,
} from 'merchant/views/AccountAndSettings/InternationalSettings/constants';
import * as services from 'merchant/views/AccountAndSettings/InternationalSettings/services';
import { render, screen, userEvent, waitFor } from 'test-utils';

const renderApp = (props = {}) => {
  render(<File {...props} />);
};

describe('Tests for File component', () => {
  const { month, year } = dateObject;

  test('File name, month, year and download button should be visible', () => {
    renderApp({ ...dateObject, file: fileObject });

    //correct file name should be visible
    expect(
      screen.getByText(`${FileName[fileObject.document_type]} - ${month} ${year}`),
    ).toBeInTheDocument();

    //download button should be visible
    expect(screen.getByText('Download')).toBeInTheDocument();
  });

  test.each(Object.values(FileType))('File name should be correct', (fileType) => {
    renderApp({ ...dateObject, file: { ...fileObject, document_type: fileType } });

    //correct file name should be visible
    expect(screen.getByText(`${FileName[fileType]} - ${month} ${year}`)).toBeInTheDocument();
  });

  test('Requested status should be visible if status is processing', () => {
    renderApp({ ...dateObject, file: { ...fileObject, file_status: FileStatus.PROCESSING } });

    //correct status should be visible
    expect(screen.getByText('Requested')).toBeInTheDocument();

    //download button should not be visible
    expect(screen.queryByText('Download')).not.toBeInTheDocument();
  });

  test('downloadFirsFile should be called with correct argument', async () => {
    renderApp({ ...dateObject, file: fileObject });

    const downloadButton = screen.getByText('Download');
    await userEvent.click(downloadButton);

    //function should be called with correct argument
    await waitFor(() =>
      expect(services.downloadFirsFile).toHaveBeenCalledWith(month, year, fileObject.id),
    );
  });
});
