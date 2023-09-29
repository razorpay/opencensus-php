import React from 'react';
import { render, screen, userEvent } from 'test-utils';

import IntegrationModal from 'merchant/views/MagicCheckout/AnalyticsSettings/common/IntegrationModal';

import * as NotificationsActions from 'merchant_common/reducers/notifications';

import { IntegrationModalPropsType } from 'merchant/views/MagicCheckout/AnalyticsSettings/types';

import {
  GOOGLE_ADS,
  GOOGLE_ADS_INTEGRATION_STEPS,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/constants';
import { INSTRUCTION_POINTS } from 'merchant/views/MagicCheckout/AnalyticsSettings/components/GoogleAds';

const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

const props: IntegrationModalPropsType = {
  stepTexts: GOOGLE_ADS_INTEGRATION_STEPS,
  demoContainer: () => <p>demo video</p>,
  modalIcon: GOOGLE_ADS.headerIcon,
  demoInfoText: 'follow the google ads dashboard guidlines',
  points: INSTRUCTION_POINTS,
  pointsHeader: 'steps to follow',
  onSavingAccountCreds: jest.fn(),
  demoVideoLink: 'https://cdn.razorpay.com/static/assets/magic-checkout/shiprocket-demo-step-1.mp4',
  setIntegrationMethod: jest.fn(),
};

describe('testing integration modal component', () => {
  beforeEach(() => {
    showNotificationSpy.mockClear();
  });

  test('component should render properly', () => {
    render(<IntegrationModal integrationModalProps={{ ...props }} />);

    expect(screen.getByText('Google Ads backend integration')).toBeInTheDocument();
  });

  test('should be able to click on skip instructions or enter credentials', async () => {
    render(<IntegrationModal integrationModalProps={{ ...props }} />);

    const skipCta = screen.getByText('Skip instructions');

    await userEvent.click(skipCta);

    //after skip cta is clicked back cta would be visible
    const backCta = screen.getByText('Back');
    await userEvent.click(backCta);

    const enterCredsCta = screen.getByText('Enter credentials');
    await userEvent.click(enterCredsCta);

    expect(screen.getByText('Conversion ID')).toBeInTheDocument();
  });

  test('should show notification message if integration modal is closed manually', async () => {
    render(<IntegrationModal integrationModalProps={{ ...props }} />);

    const modalCloseCta = screen.getByTestId('modal-header-close-btn');

    await userEvent.click(modalCloseCta);
    expect(showNotificationSpy).toHaveBeenCalled();
  });
});
