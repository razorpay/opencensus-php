import { mockContextData } from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/__test__/fixtures/mocks';
import FIRSTable from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/FIRSTable';
import { render, screen, waitFor } from 'test-utils';

jest.mock(
  'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/FIRSTable/FirsList',
  () => ({
    __esModule: true,
    default: () => <div data-testid="firs-list">Firs List</div>,
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/FIRSTable/DropdownAction',
  () => ({
    __esModule: true,
    default: () => <div data-testid="dropdown-action">Dropdown Action</div>,
  }),
);

const renderApp = (props = {}) => {
  render(<FIRSTable {...props} />);
};

describe('Tests for FIRSTable component', () => {
  const getFirsData = jest.fn();

  test('Note and components should be visible on init', () => {
    mockContextData({ getFirsData });

    renderApp();

    //note should be visible
    expect(
      screen.getByText(
        'Note: FIRS are only available for months with at least one international payment',
      ),
    ).toBeInTheDocument();

    //components should be visible
    expect(screen.getByTestId('dropdown-action')).toBeInTheDocument();
    expect(screen.getByTestId('firs-list')).toBeInTheDocument();
  });

  test('getFirsData should be called on init', () => {
    mockContextData({ getFirsData });

    renderApp();

    //note should be visible
    expect(getFirsData).toHaveBeenCalledWith(new Date().getFullYear());
  });

  test('setError and showNotification should be called on error', async () => {
    const setError = jest.fn();
    const error = 'Dummy error';
    mockContextData({ getFirsData, error, setError });

    renderApp();

    await waitFor(() => {
      expect(setError).toHaveBeenCalledWith(null);
    });
  });
});
