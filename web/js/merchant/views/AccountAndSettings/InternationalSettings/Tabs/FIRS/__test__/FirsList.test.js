import {
  dateObject,
  generateBankFirs,
  generateInternalFirs,
} from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/__test__/fixtures';
import { mockContextData } from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/__test__/fixtures/mocks';
import FirsList from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/FIRSTable/FirsList';
import {
  BANK_FILES,
  INTERNAL_FILES,
  FileType,
  FileStatus,
  PopupType,
} from 'merchant/views/AccountAndSettings/InternationalSettings/constants';
import * as utils from 'merchant/views/AccountAndSettings/InternationalSettings/utils';
import { render, screen, waitFor, userEvent } from 'test-utils';

jest.mock(
  'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/FIRSTable/EmptyListContainer',
  () => ({
    __esModule: true,
    default: () => <div data-testid="empty-list-container">Empty List Container</div>,
  }),
);

const renderApp = (props) => {
  render(<FirsList {...props} />);
};

describe('Tests for FirsList component', () => {
  const setPopupData = jest.fn();
  const { year, month } = dateObject;
  const popupData = {
    isOpen: false,
  };
  test('When firsData is empty for selected', () => {
    mockContextData({ firsData: {}, listYear: year, popupData });

    renderApp();

    expect(screen.getByTestId('empty-list-container')).toBeInTheDocument();
  });

  test('When firsData is available with no files', async () => {
    const firsData = { [year]: { [month]: [] } };
    mockContextData({ firsData, listYear: year, popupData, setPopupData });

    renderApp();

    expect(screen.queryByTestId('empty-list-container')).not.toBeInTheDocument();

    expect(screen.getByText('No FIRS generated yet')).toBeInTheDocument();
    expect(screen.getByText('Why?')).toBeInTheDocument();

    //testing click event
    await userEvent.click(screen.getByText('Why?'));
    await waitFor(() => {
      expect(setPopupData).toHaveBeenCalledWith(
        expect.objectContaining({
          type: PopupType.NO_FIRS,
          month,
          year,
          isOpen: true,
        }),
      );
    });
  });

  test('When firsData is available with files', async () => {
    const firsData = { [year]: { [month]: generateBankFirs(BANK_FILES) } };
    mockContextData({ firsData, listYear: year, popupData, setPopupData });

    renderApp();

    expect(screen.getByText(`Download FIRS files (${BANK_FILES.length})`)).toBeInTheDocument();

    //testing click event
    await userEvent.click(screen.getByRole('button'));
    await waitFor(() => {
      expect(setPopupData).toHaveBeenCalledWith(
        expect.objectContaining({
          type: PopupType.DOWNLOAD_FIRS,
          month,
          year,
          isOpen: true,
        }),
      );
    });
  });

  test('When only internalFirs is available with status noData', () => {
    const firsData = {
      [year]: { [month]: generateInternalFirs([FileType.FIRS_INTERNAL_FILE], FileStatus.NO_DATA) },
    };
    mockContextData({ firsData, listYear: year, popupData });

    renderApp();

    expect(screen.getByText('No international payments')).toBeInTheDocument();
  });

  test('When only internalFirs is available with status processing', () => {
    const firsData = {
      [year]: {
        [month]: generateInternalFirs([FileType.FIRS_INTERNAL_FILE], FileStatus.PROCESSING),
      },
    };
    mockContextData({ firsData, listYear: year, popupData });

    renderApp();

    expect(screen.getByText('FIRS Requested')).toBeInTheDocument();
  });

  test('When only internalFirs is available with status failed', () => {
    const firsData = {
      [year]: {
        [month]: generateInternalFirs([FileType.FIRS_INTERNAL_FILE], FileStatus.FAILED),
      },
    };
    mockContextData({ firsData, listYear: year, popupData });

    renderApp();

    expect(screen.getByText('No FIRS generated yet')).toBeInTheDocument();
    expect(screen.getByText('Why?')).toBeInTheDocument();
  });

  test('When amex and internalFirs is available with status noData', () => {
    const firsData = {
      [year]: {
        [month]: generateInternalFirs(INTERNAL_FILES, FileStatus.NO_DATA),
      },
    };
    mockContextData({ firsData, listYear: year, popupData });

    renderApp();

    expect(screen.queryByText('No international payments')).not.toBeInTheDocument();
    expect(screen.getByText(`Download FIRS files (1)`)).toBeInTheDocument();
  });

  test('When amex and internalFirs is available with status failed', () => {
    const firsData = {
      [year]: {
        [month]: generateInternalFirs(INTERNAL_FILES, FileStatus.FAILED),
      },
    };
    mockContextData({ firsData, listYear: year, popupData });

    renderApp();

    expect(screen.getByText(`Download FIRS files (1)`)).toBeInTheDocument();
  });

  test('New badge should be visible if isRecentMonth returns true', () => {
    const isRecentMonth = jest.spyOn(utils, 'isRecentMonth').mockReturnValue(true);
    const firsData = {
      [year]: {
        [month]: generateInternalFirs([FileType.FIRS_INTERNAL_AMEX_FILE]),
      },
    };
    mockContextData({ firsData, listYear: year, popupData });

    renderApp();

    expect(isRecentMonth).toHaveBeenCalledWith(month, year);
    expect(screen.getByText('New')).toBeInTheDocument();

    isRecentMonth.mockRestore();
  });

  test('New badge should not be visible by default', () => {
    const isRecentMonth = jest.spyOn(utils, 'isRecentMonth');
    const firsData = {
      [year]: {
        [month]: generateInternalFirs([FileType.FIRS_INTERNAL_AMEX_FILE]),
      },
    };
    mockContextData({ firsData, listYear: year, popupData });

    renderApp();

    expect(isRecentMonth).toHaveBeenCalledWith(month, year);
    expect(screen.queryByText('New')).not.toBeInTheDocument();
  });
});
