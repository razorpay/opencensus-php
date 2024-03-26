import UploadButton from 'merchant/views/RiskAndFraud/RiskAnalytics/BlockRule/UploadButton';
import { render, screen, userEvent } from 'test-utils';

import { SAMPLE_XML_FILE } from '../constants';

const renderApp = (props = {}) => {
  render(<UploadButton {...props} />);
};

const createDummyFile = () => {
  const blob = new Blob(['upload text file']);
  const file = new File([blob], 'test.csv', {
    type: 'text/plain',
  });
  return file;
};

describe('Tests for UploadButton component (Risk Visibility)', () => {
  test('Should render without breaking', () => {
    renderApp();
    expect(screen.getByText(/Upload XLS or XLSV of list items/)).toBeInTheDocument();
  });

  test.skip('Should call onFileChange when file is selected', async () => {
    const onFileChange = jest.fn();
    renderApp({ onFileChange });

    const fileInput = screen.getByTestId('file-input');
    await userEvent.click(screen.getByText(/Upload XLS or XLSV of list items/));
    await userEvent.upload(fileInput, createDummyFile());

    await expect(screen.getByText(/test.csv/)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Close' })).toBeInTheDocument();
    expect(onFileChange).toHaveBeenCalled();

    expect(screen.queryByText(/Upload XLS or XLSV of list items/)).not.toBeInTheDocument();
  });

  test.skip('Should call onFileChange when file is removed', async () => {
    const onFileChange = jest.fn();
    renderApp({ onFileChange });

    const fileInput = screen.getByTestId('file-input');
    await userEvent.click(screen.getByText(/Upload XLS or XLSV of list items/));
    await userEvent.upload(fileInput, createDummyFile());

    await expect(screen.getByText(/test.csv/)).toBeInTheDocument();
    await userEvent.click(screen.getByRole('button', { name: 'Close' }));

    await expect(onFileChange).toHaveBeenCalledWith(null);
    expect(screen.getByText(/Upload XLS or XLSV of list items/)).toBeInTheDocument();
  });

  test('Should show download sample file button', async () => {
    window.open = jest.fn();
    renderApp();

    expect(screen.getByText(/sample XLS file/)).toBeInTheDocument();
    await userEvent.click(screen.getByText(/sample XLS file/));

    expect(window.open).toHaveBeenCalledWith(SAMPLE_XML_FILE, '_blank');
  });
});
