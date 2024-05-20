import React from 'react';

import PartnerAccountsSettings from 'merchant/views/PartnerDashboard/PartnerAccountsSettings';
import { screen, render } from 'test-utils';

jest.mock('merchant/views/TicketSupport/components/TicketsContainer', () => ({
  __esModule: true,
  default: () => <div>TicketsContainer</div>,
}));

jest.mock('merchant/views/TicketSupport/components/Tickets', () => ({
  __esModule: true,
  default: () => <div>Tickets</div>,
}));

describe('PartnerAccountsSettings', () => {
  const renderApp = () => {
    return render(<PartnerAccountsSettings />, {
      renderViaRouteGuard: false,
    });
  };
  it('renders support ticket page', () => {
    renderApp();
    expect(screen.getByText('Tickets')).toBeInTheDocument();
  });
});
