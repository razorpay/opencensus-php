import { dateObject } from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/__test__/fixtures';
import { mockContextData } from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/__test__/fixtures/mocks';
import DropdownAction from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/FIRSTable/DropdownAction';
import { render, screen, userEvent, waitFor } from 'test-utils';

const renderApp = (props = {}) => {
  render(<DropdownAction {...props} />);
};

describe('Tests for DropdownAction Component', () => {
  const { year } = dateObject;

  test('Dropdown label and default year should be visible on init', () => {
    mockContextData({ listYear: year });

    renderApp();

    expect(screen.getByText('Year')).toBeInTheDocument();
    expect(screen.getAllByRole('combobox')[0].getAttribute('value')).toBe(year.toString());
  });

  test('getFirsData and setListYear should be called on year change', async () => {
    const getFirsData = jest.fn((year, month, callback) => callback());
    const setListYear = jest.fn();
    mockContextData({ listYear: year, setListYear, getFirsData });

    renderApp();

    await userEvent.click(screen.getAllByRole('combobox')[0]);
    await userEvent.click(screen.getByRole('option', { name: '2021' }));

    await waitFor(() => {
      expect(getFirsData).toHaveBeenCalledWith(2021, undefined, expect.any(Function));
      expect(setListYear).toHaveBeenCalledWith(2021);
    });
  });
});
