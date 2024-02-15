import React from 'react';

import FtuxTooltip from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteNavLinks/components/FtuxTooltip';
import * as ftuxVisibilityAction from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteNavLinks/components/FtuxTooltip/ftuxVisibility';
import { render, screen, userEvent } from 'test-utils';

const renderApp = ({ props }: { props?: Record<string, any> }) =>
  render(<FtuxTooltip {...props} />);

describe('FtuxTooltip', () => {
  test('should render flux tooltip its visible state is true', () => {
    jest.spyOn(ftuxVisibilityAction, 'isVisible').mockImplementation(() => true);
    renderApp({});
    expect(screen.getByTestId('component-wrapper')).toHaveTextContent(
      'All Affiliate Accounts that have accepted your invite. Perform KYC for your sub-merchants and accelerate their onboarding.',
    );
  });

  test('should not render flux tooltip its visible state is false', () => {
    jest.spyOn(ftuxVisibilityAction, 'isVisible').mockImplementation(() => false);
    renderApp({});
    expect(screen.queryByText('GOT IT')).not.toBeInTheDocument();
  });

  test('should hide flux tooltip on clicking Got it', async () => {
    jest.spyOn(ftuxVisibilityAction, 'isVisible').mockImplementation(() => true);
    renderApp({});
    const hideAction = screen.getByText('GOT IT');
    expect(hideAction).toBeInTheDocument();
    await userEvent.click(hideAction);
    expect(
      screen.getByText(
        'List of all invites you have sent out. Switch to Accepted Invites tab for Affiliates that have accepted your invite.',
      ),
    ).toBeInTheDocument();

    jest.spyOn(ftuxVisibilityAction, 'isVisible').mockImplementation(() => false);
    await userEvent.click(screen.getByText('GOT IT'));
    expect(screen.queryByText('GOT IT')).not.toBeInTheDocument();
  });
});
