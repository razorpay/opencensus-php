import { mockContextData } from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/__test__/fixtures/mocks';
import DownloadPopup from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/DownloadPopup';
import {
  PopupType,
  PopupTitle,
} from 'merchant/views/AccountAndSettings/InternationalSettings/constants';
import { render, screen, userEvent, waitFor } from 'test-utils';

jest.mock(
  'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/DownloadPopup/FirsFiles',
  () => ({
    __esModule: true,
    default: () => <div data-testid="firs-files">Firs Files</div>,
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/DownloadPopup/YearMonthDropdown',
  () => ({
    __esModule: true,
    default: () => <div data-testid="year-month-dropdown">Year Month Dropdown</div>,
  }),
);

const renderApp = (props = {}) => {
  render(<DownloadPopup {...props} />);
};

describe('Tests for DownloadPopup component', () => {
  const popupData = {
    isOpen: true,
    type: PopupType.DOWNLOAD_FIRS,
  };

  test('Modal title and components should be visible on intial load', () => {
    mockContextData({ popupData });

    renderApp();
    //title should be visible and correct
    expect(screen.getByText(PopupTitle[PopupType.DOWNLOAD_FIRS])).toBeInTheDocument();

    //component should be visible
    expect(screen.getByTestId('firs-files')).toBeInTheDocument();
    expect(screen.getByTestId('year-month-dropdown')).toBeInTheDocument();
  });

  test('Loader should be visible if isLoading is true', () => {
    mockContextData({ popupData, isLoading: true });

    renderApp();

    //spinner should be visible
    expect(screen.getByRole('progressbar', { name: 'firs-modal-spinner' })).toBeInTheDocument();

    //YearMonthDropdown should be visible
    expect(screen.getByTestId('year-month-dropdown')).toBeInTheDocument();

    //FirsList should be hidden
    expect(screen.queryByTestId('firs-files')).not.toBeInTheDocument();
  });

  test('Close icon should be visible and functional', async () => {
    const setPopupData = jest.fn();
    mockContextData({
      popupData,
      setPopupData,
    });
    renderApp();

    //close icon should be visible
    expect(screen.getByLabelText('Close')).toBeInTheDocument();
    await userEvent.click(screen.getByLabelText('Close'));
    await waitFor(() => expect(setPopupData).toHaveBeenCalled());
  });
});
