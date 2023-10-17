import { dateObject } from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/__test__/fixtures';
import { mockContextData } from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/__test__/fixtures/mocks';
import YearMonthDropdown from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/DownloadPopup/YearMonthDropdown';
import * as utils from 'merchant/views/AccountAndSettings/InternationalSettings/utils';
import { render, screen, userEvent, waitFor } from 'test-utils';

const renderApp = (props = {}) => {
  render(<YearMonthDropdown {...props} />);
};

describe('Tests for YeaMonthDropdown', () => {
  let isMonthValidSpy;

  beforeAll(() => {
    isMonthValidSpy = jest.spyOn(utils, 'isMonthValid');
    isMonthValidSpy.mockReturnValue(true);
  });

  afterAll(() => {
    isMonthValidSpy.mockRestore();
  });

  test('Correct labels should visible', () => {
    mockContextData({ popupData: dateObject });
    renderApp();

    expect(screen.getByText('Year')).toBeInTheDocument();
    expect(screen.getByText('Month')).toBeInTheDocument();
  });

  test('Dropdown should display correct default value if passed through context', () => {
    mockContextData({ popupData: dateObject });
    renderApp();

    expect(screen.getAllByText(dateObject.year)).toHaveLength(2);
    expect(screen.getAllByText(dateObject.month)).toHaveLength(2);
  });

  test('getFirsData should be called when year is changed', async () => {
    const setPopupData = jest.fn();
    const getFirsData = jest.fn((year, month, callback) => callback());
    mockContextData({ popupData: dateObject, setPopupData, getFirsData });
    renderApp();

    await userEvent.click(screen.getAllByRole('combobox')[0]);
    await userEvent.click(screen.getByRole('option', { name: '2021' }));
    await waitFor(() => expect(getFirsData).toHaveBeenCalled());
    expect(getFirsData).toHaveBeenCalledWith(2021, undefined, expect.any(Function));

    expect(setPopupData).toHaveBeenCalled();
  });

  test('getFirsData should be called with correct callback when year is changed but month is not valid', async () => {
    const setPopupData = jest.fn();
    const getFirsData = jest.fn((year, month, callback) => callback());
    isMonthValidSpy.mockImplementation(
      (month, year) => !(month == dateObject.month && year == 2021),
    );
    mockContextData({ popupData: dateObject, setPopupData, getFirsData });
    renderApp();

    //test particular condition here
    await userEvent.click(screen.getAllByRole('combobox')[0]);
    await userEvent.click(screen.getByRole('option', { name: '2021' }));
    await waitFor(() => expect(getFirsData).toHaveBeenCalled());
    expect(getFirsData).toHaveBeenCalledWith(2021, undefined, expect.any(Function));

    expect(setPopupData).toHaveBeenCalled();
  });

  test("month shouldn't be visible if isMonthValid returns false", async () => {
    isMonthValidSpy.mockImplementation((month) => month !== 'January');
    mockContextData({ popupData: dateObject });
    renderApp();

    //test particular condition here
    await userEvent.click(screen.getAllByRole('combobox')[1]);
    await waitFor(() => {
      //January shouldn;t be visible
      expect(screen.queryByRole('option', { name: 'January' })).not.toBeInTheDocument();

      //Any month other than January should be visible
      expect(screen.getByRole('option', { name: dateObject.month })).toBeInTheDocument();
    });
  });

  test('getFirsData should not be called when month is changed', async () => {
    const setPopupData = jest.fn();
    const getFirsData = jest.fn();
    mockContextData({ popupData: dateObject, setPopupData, getFirsData });
    renderApp();

    await userEvent.click(screen.getAllByRole('combobox')[1]);
    await userEvent.click(screen.getByRole('option', { name: 'March' }));
    await waitFor(() => expect(getFirsData).not.toHaveBeenCalled());

    expect(setPopupData).toHaveBeenCalled();
  });
});
