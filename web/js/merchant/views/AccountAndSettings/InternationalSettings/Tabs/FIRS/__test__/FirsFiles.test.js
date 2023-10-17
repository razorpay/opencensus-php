import {
  dateObject,
  generateBankFirs,
  generateInternalFirs,
} from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/__test__/fixtures';
import { mockContextData } from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/__test__/fixtures/mocks';
import FirsFiles from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/DownloadPopup/FirsFiles';
import {
  BANK_FILES,
  INTERNAL_FILES,
  FileType,
  FileStatus,
} from 'merchant/views/AccountAndSettings/InternationalSettings/constants';
import { render, screen, userEvent, waitFor } from 'test-utils';

jest.mock(
  'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/DownloadPopup/File',
  () => ({
    __esModule: true,
    default: ({ file, month, year }) => (
      <div data-testid="file-row">{`${month} - ${year} - ${file.id}`}</div>
    ),
  }),
);

const renderApp = (props = {}) => {
  render(<FirsFiles {...props} />);
};

describe('Tests for FirsFiles component when only bank FIRS are available', () => {
  const { month, year } = dateObject;

  test('When only Bank Firs are present', () => {
    const bankFirs = generateBankFirs(BANK_FILES.slice(0, 2));
    const firsData = { [year]: { [month]: bankFirs } };
    mockContextData({ firsData, popupData: dateObject });

    renderApp();

    //bank firs count should be visible
    expect(screen.getByText(`Bank Firs (${bankFirs.length} files available)`, { exact: false }));

    //show all button shouldn't be visible if files are less than 4
    expect(screen.queryByText('Show all')).not.toBeInTheDocument();

    //file component should have all the required props
    expect(screen.getAllByTestId('file-row')).toHaveLength(bankFirs.length);
    bankFirs.forEach((file) => {
      expect(screen.getByText(`${month} - ${year} - ${file.id}`)).toBeInTheDocument();
    });

    //internal firs section shouldn't be visible
    expect(screen.queryByText('Razorpay Statements', { exact: false })).not.toBeInTheDocument();

    //infotext shouldn't be visible
    expect(
      screen.queryByText(
        'Bank FIRS is/are usually available for download after the 15th of the next month.',
      ),
    ).not.toBeInTheDocument();
  });

  test('When No Bank Firs are present', () => {
    const bankFirs = [];
    const firsData = { [year]: { [month]: bankFirs } };
    mockContextData({ firsData, popupData: dateObject });

    renderApp();

    //bank firs count should be visible
    expect(screen.getByText(`Bank Firs (No file available)`, { exact: false }));

    //infotext should be visible
    expect(
      screen.getByText(
        'Bank FIRS is/are usually available for download after the 15th of the next month.',
      ),
    ).toBeInTheDocument();
  });

  test('When more than 3 Bank Firs are present show all should be visible', async () => {
    const bankFirs = generateBankFirs([...BANK_FILES, ...BANK_FILES]);
    const firsData = { [year]: { [month]: bankFirs } };
    mockContextData({ firsData, popupData: dateObject });

    renderApp();

    //Show all should be visible
    expect(screen.getByText('Show all')).toBeInTheDocument();

    //3 files should be visible initially
    expect(screen.getAllByTestId('file-row')).toHaveLength(3);

    //show all click
    await userEvent.click(screen.getByText('Show all'));
    await waitFor(() => {
      expect(screen.getByText('Collapse all')).toBeInTheDocument();
    });
    expect(screen.getAllByTestId('file-row')).toHaveLength(bankFirs.length);

    //collapse all click
    await userEvent.click(screen.getByText('Collapse all'));
    await waitFor(() => {
      expect(screen.getByText('Show all')).toBeInTheDocument();
    });
    //should defult back to 3 files
    expect(screen.getAllByTestId('file-row')).toHaveLength(3);
  });
});

describe('Tests for FirsFiles when only internal FIRS are available', () => {
  const { month, year } = dateObject;

  test('When only amex file is available', () => {
    const internalFirs = generateInternalFirs([FileType.FIRS_INTERNAL_AMEX_FILE]);
    const firsData = { [year]: { [month]: internalFirs } };
    mockContextData({ firsData, popupData: dateObject });

    renderApp();

    //internal firs count should be visible
    expect(screen.getByText(`Razorpay Statements (1 file available)`, { exact: false }));

    //files should be visible
    expect(screen.getAllByTestId('file-row')).toHaveLength(internalFirs.length);
    internalFirs.forEach((file) => {
      expect(screen.getByText(`${month} - ${year} - ${file.id}`)).toBeInTheDocument();
    });

    //request button should be visible with valid subtext
    expect(screen.getByText('Request for Razorpay statement')).toBeInTheDocument();
    expect(
      screen.getByText(
        "Don't see bank FIRS above or find any international transactions missing?",
        { exact: false },
      ),
    ).toBeInTheDocument();
  });

  test('When only internal firs file is available with status processed', () => {
    const internalFirs = generateInternalFirs([FileType.FIRS_INTERNAL_FILE]);
    const firsData = { [year]: { [month]: internalFirs } };
    mockContextData({ firsData, popupData: dateObject });

    renderApp();

    //internal firs count should be visible
    expect(screen.getByText(`Razorpay Statements (1 file available)`, { exact: false }));

    //files should be visible
    expect(screen.getAllByTestId('file-row')).toHaveLength(internalFirs.length);
    internalFirs.forEach((file) => {
      expect(screen.getByText(`${month} - ${year} - ${file.id}`)).toBeInTheDocument();
    });

    //Request button shouldn't be visible when internal firs is in processed status
    expect(screen.queryByText('Request for Razorpay statement')).not.toBeInTheDocument();
  });

  test('When only internal firs file is available with status processing', () => {
    const internalFirs = generateInternalFirs([FileType.FIRS_INTERNAL_FILE], FileStatus.PROCESSING);
    const firsData = { [year]: { [month]: internalFirs } };
    mockContextData({ firsData, popupData: dateObject });

    renderApp();

    //internal firs count should be visible
    expect(screen.getByText(`Razorpay Statements (1 file available)`, { exact: false }));

    //files should be visible
    expect(screen.getAllByTestId('file-row')).toHaveLength(internalFirs.length);
    internalFirs.forEach((file) => {
      expect(screen.getByText(`${month} - ${year} - ${file.id}`)).toBeInTheDocument();
    });

    //Request button shouldn't be visible when internal firs is in processed status
    expect(screen.queryByText('Request for Razorpay statement')).not.toBeInTheDocument();
  });

  test('When only internal firs file is available with status failed', () => {
    const internalFirs = generateInternalFirs([FileType.FIRS_INTERNAL_FILE], FileStatus.FAILED);
    const firsData = { [year]: { [month]: internalFirs } };
    mockContextData({ firsData, popupData: dateObject });

    renderApp();

    //internal firs count should be visible
    expect(screen.queryByText(`Razorpay Statements`, { exact: false })).not.toBeInTheDocument();

    //files should be visible
    expect(screen.queryAllByTestId('file-row')).toHaveLength(0);

    //Request button should be visible with valid subtext when internal firs is in failed status
    expect(screen.getByText('Request for Razorpay statement')).toBeInTheDocument();
    expect(
      screen.getByText(`Your FIRS request for ${month} ${year} failed due to technical reasons.`, {
        exact: false,
      }),
    ).toBeInTheDocument();
  });

  test('When no files are available and request button is clicked', async () => {
    const firsData = { [year]: { [month]: [] } };
    const setPopupData = jest.fn();
    mockContextData({ firsData, popupData: dateObject, setPopupData });

    renderApp();

    //Request button shouldn't be visible when internal firs is in processed status
    expect(screen.getByText('Request for Razorpay statement')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Request for Razorpay statement'));

    //setPopupData should be called
    await waitFor(() => {
      expect(setPopupData).toHaveBeenCalledWith(expect.any(Function));
    });
  });

  test('When both internal and amex firs file is available ', () => {
    const internalFirs = generateInternalFirs(INTERNAL_FILES);
    const firsData = { [year]: { [month]: internalFirs } };
    mockContextData({ firsData, popupData: dateObject });

    renderApp();

    //internal firs count should be visible
    expect(screen.getByText(`Razorpay Statements (2 files available)`, { exact: false }));

    //files should be visible
    expect(screen.queryAllByTestId('file-row')).toHaveLength(2);
  });
});
