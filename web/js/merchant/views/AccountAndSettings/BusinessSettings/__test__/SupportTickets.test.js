import SupportTickets from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/SupportTickets';
import { render, screen } from 'test-utils';

jest.mock('merchant/views/TicketSupport/components/TicketsContainer', () => ({
  __esModule: true,
  default: () => <div>TicketsContainer</div>,
}));

jest.mock('merchant/views/TicketSupport/components/Tickets', () => ({
  __esModule: true,
  default: () => <div>Tickets</div>,
}));

describe('Support Tickets', () => {
  const renderApp = (user) => {
    render(<SupportTickets />, { initialState: { session: { user } } });
  };

  test('should render Tickets Container when isMobileSignupCareActive is true', () => {
    renderApp({ isMobileSignupCareActive: true });
    expect(screen.getByText('TicketsContainer')).toBeInTheDocument();
    expect(screen.queryByText('Tickets')).not.toBeInTheDocument();
  });

  test('should render Tickets when isMobileSignupCareActive is false', () => {
    renderApp({ isMobileSignupCareActive: false });
    expect(screen.getByText('Tickets')).toBeInTheDocument();
    expect(screen.queryByText('TicketsContainer')).not.toBeInTheDocument();
  });
});
