import React from 'react';

import SocialShareGroup from 'merchant/views/PartnerDashboard/SubMerchant/components/SocialShareGroup';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { render, screen, userEvent } from 'test-utils';

// TODO: only basic render test added, other tests can be added later.

const defaultProps = {
  referralUrl: 'https://rzp.io/i/6cxEuFXr',
  tracking: { trackEvent: jest.fn() },
  product: PRODUCT_TYPE.PG,
  source: 'source',
  partnerID: 'partnerID',
};

describe('SocialShareGroup', () => {
  let windowOpenSpy;

  beforeEach(() => {
    document.execCommand = jest.fn();
    windowOpenSpy = jest.spyOn(window, 'open');
    window.rzpQ = {
      onbr: () => ({ clicked: () => {} }),
    };
  });

  afterEach(() => {
    windowOpenSpy.mockRestore();
  });

  test('should render in default setting', () => {
    render(<SocialShareGroup {...defaultProps} />, {});
    expect(screen.getByRole('button', { name: 'Copy Link' })).toBeVisible();
    expect(screen.getByText('Or Share Via')).toBeVisible();
  });

  test('should open a window on clicking share via image', async () => {
    windowOpenSpy.mockImplementation(() => {});
    render(<SocialShareGroup {...defaultProps} />, {});
    const image = screen.getByAltText('share via fb');
    await userEvent.click(image);
    expect(windowOpenSpy).toHaveBeenCalledWith(
      `https://www.facebook.com/sharer/sharer.php?u=https://rzp.io/i/6cxEuFXr&quote=%22Sign%20up%20on%20Razorpay!%22%3A%20Start%20using%20a%20wide%20range%20of%20Razorpay's%20payment%20solutions%20and%20unlock%20growth%20for%20your%20business%20with%20just%20a%20few%20clicks.%20Go%20live%20in%20less%20than%2010%20minutes.`,
      'facebook-share',
      'width=550,height=235',
    );
  });
});
