import EmptyListContainer from 'merchant/views/AccountAndSettings/InternationalSettings/Tabs/FIRS/components/FIRSTable/EmptyListContainer';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import { render, screen, userEvent, waitFor } from 'test-utils';

jest.mock('react-router-dom', () => ({
  __esModule: true,
  ...jest.requireActual('react-router-dom'),
  useNavigate: jest.fn(),
}));

const renderApp = (props = {}) => {
  render(<EmptyListContainer {...props} />);
};

describe('Tests for EmptyListContainer component', () => {
  test('Title and button should be visible', () => {
    renderApp();
    expect(screen.getByText('Start collecting international payments')).toBeInTheDocument();
    expect(screen.getByText('Request International enablement')).toBeInTheDocument();
  });

  test('Button click should call navigate function with correct arguments', async () => {
    const mockNavigate = jest.fn();
    require('react-router-dom').useNavigate.mockReturnValue(mockNavigate);

    renderApp();

    await userEvent.click(screen.getByText('Request International enablement'));
    await waitFor(() => {
      expect(mockNavigate).toHaveBeenCalledWith(ROUTES_INFO.INTERNATIONAL_PAYMENTS);
    });
  });
});
