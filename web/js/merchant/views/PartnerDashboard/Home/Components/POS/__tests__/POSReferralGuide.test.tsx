import React from 'react';

import * as trackEvents from 'common/utils/analytics';
import POSReferralGuide from 'merchant/views/PartnerDashboard/Home/Components/POS/POSReferralGuide';
import { render, screen, userEvent, waitFor } from 'test-utils';

const referClient = jest.fn();

describe('POSReferralGuide', () => {
  const analyticsTrackWithUserSpy = jest.spyOn(trackEvents, 'analyticsTrackWithUserInfo');
  const renderApp = () => {
    return render(<POSReferralGuide handleReferClient={referClient} />, {
      showModal: true,
      initialState: {
        session: {
          user: {
            user: {
              email: 'omnitest@gmail.com',
              contact_mobile: '987763437438',
            },
          },
        },
      },
    });
  };

  test('should render with all the components', () => {
    renderApp();

    const heading = screen.getByText('Refer clients to Razorpay POS!');
    expect(heading).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Refer Now' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Add POS Agent' })).toBeInTheDocument();
    expect(analyticsTrackWithUserSpy).toBeCalledTimes(1);
    expect(analyticsTrackWithUserSpy).toHaveBeenCalledWith({
      objectName: 'Partner Dashboard POS Banner',
      actionName: 'Loaded',
      screen: 'Partner Dashboard Home',
      properties: {
        screen: 'partner_dashboard_homepage',
      },
    });
  });

  test('should handle Refer Now button click', async () => {
    renderApp();

    const referButton = screen.getByRole('button', { name: 'Refer Now' });
    expect(referButton).toBeInTheDocument();
    await userEvent.click(referButton);

    expect(referClient).toHaveBeenCalledTimes(1);
    expect(analyticsTrackWithUserSpy).toHaveBeenCalledWith({
      objectName: 'Partner Dashboard Homepage Cta',
      actionName: 'Clicked',
      screen: 'Partner Dashboard Home',
      properties: {
        screen: 'partner_dashboard_homepage',
        ctaClicked: 'Refer Now',
      },
    });
  });

  test('should handle Add POS Agent button click', async () => {
    renderApp();

    const addAgentButton = screen.getByRole('button', { name: 'Add POS Agent' });
    expect(addAgentButton).toBeInTheDocument();
    await userEvent.click(addAgentButton);
    expect(analyticsTrackWithUserSpy).toHaveBeenCalledWith({
      objectName: 'Partner Dashboard Homepage Cta',
      actionName: 'Clicked',
      screen: 'Partner Dashboard Home',
      properties: {
        screen: 'partner_dashboard_homepage',
        ctaClicked: 'Add POS Agent',
      },
    });
    expect(analyticsTrackWithUserSpy).toHaveBeenCalledWith({
      objectName: 'Agent Invite Cta',
      actionName: 'Clicked',
      screen: 'Partner Dashboard Home',
      properties: {
        screen: 'partner_dashboard_homepage',
      },
    });
    await waitFor(() => {
      expect(screen.getByText('Invite New Member')).toBeInTheDocument();
    });
  });
});
