import React from 'react';
import { screen, render, userEvent, waitFor } from 'test-utils';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import CommsBannerItem from 'merchant/views/POS/CommsBanner/CommsBannerItem';
import 'jest-location-mock';

jest.spyOn(analytics, 'track_EXPERIMENTAL');

describe('Communications Banner item component', () => {
  it('should call track_EXPERIMENTAL with the correct parameters on cta click', async () => {
    window.location.assign = jest.fn();
    render(
      <CommsBannerItem
        commsItem={{
          status: 'notice',
          title: 'POS Details Required',
          description: 'Please add few extra details to get activated with Razorpay POS.',
          cta: [
            {
              name: 'Add Details',
              url: 'https://easy.razorpay.com/onboarding',
              type: 'button',
            },
          ],
        }}
        leftConnector={{
          isHidden: false,
          variant: 'notice',
        }}
        rightConnector={{
          isHidden: true,
          variant: 'neutral',
        }}
        isLast
      />,
    );

    expect(screen.getByRole('button', { name: 'Add Details' })).toBeInTheDocument();

    await userEvent.click(screen.getByRole('button', { name: 'Add Details' }));

    await waitFor(() => {
      expect(analytics.track_EXPERIMENTAL).toHaveBeenCalledWith(SignUpEvents.websiteCtaClicked, {
        label: 'Add Details',
        section: 'Merchant Activation Status Bar',
        whatsAppUpdates: 'No',
        subSection: 'POS Catalog',
        l1FunnelStage: 'Device Exploration',
        l2FunnelStage: 'Merchant Activation Status Bar - POS Catalog',
      });
    });
    expect(window.location.assign).toHaveBeenCalledWith('https://easy.razorpay.com/onboarding');
  });
});
