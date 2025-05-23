import React from 'react';
import OnboardingHeader from '../OnboardingHeader';
import { render, screen, userEvent } from 'apps/pos/src/services/test/test-utils';
import { useLocation } from 'react-router-dom';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';

export const mockNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: () => mockNavigate,
  useLocation: jest.fn(),
}));

jest.mock('apps/pos/src/services/analytics', () => ({
  ...jest.requireActual('apps/pos/src/services/analytics'),
  trackEvent: jest.fn(),
}));

describe('<OnboardingHeader/>', () => {
  const mockPathname = (path: string) => {
    (useLocation as jest.Mock).mockReturnValue({
      pathname: path,
    });
  };
  test('should render onboarding header on screen', () => {
    const props = {
      pageLabel: 'Some Random Step',
      title: 'Some Random Title',
      description: 'Some Random Description',
    };

    render(<OnboardingHeader {...props} />);
    expect(screen.getByText('Some Random Step')).toBeInTheDocument();
    expect(screen.getByText('Some Random Title')).toBeInTheDocument();
    expect(screen.getByText('Some Random Description')).toBeInTheDocument();
  });

  test('should track analytics for deviceSelectionCatalog path', async () => {
    mockPathname('/deviceSelectionCatalog');
    const props = {
      isBackButtonVisible: true,
    };

    render(<OnboardingHeader {...props} />);
    await userEvent.click(screen.getByLabelText('header-back-btn'));

    expect(trackEvent).toHaveBeenCalledWith({
      eventName: analyticsTypes.ANALYTICS_EVENTS.ICON,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        type: 'Back Icon',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.DEVICE_EXPLORATION,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.POS_PRODUCT_DESCRIPTION,
      },
    });
  });

  test('should track analytics for mobileNumberVerify path', async () => {
    mockPathname('/mobileNumberVerify');
    const props = {
      isBackButtonVisible: true,
    };

    render(<OnboardingHeader {...props} />);
    await userEvent.click(screen.getByLabelText('header-back-btn'));

    expect(trackEvent).toHaveBeenCalledWith({
      eventName: analyticsTypes.ANALYTICS_EVENTS.ICON,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        type: 'Back Icon',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.MERCHANT_SIGNUP,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.MOBILE_NUMBER_ENTRY,
      },
    });
  });

  test('should track analytics for nachForm path', async () => {
    mockPathname('/nachForm');
    const props = {
      isBackButtonVisible: true,
    };

    render(<OnboardingHeader {...props} />);
    await userEvent.click(screen.getByLabelText('header-back-btn'));

    expect(trackEvent).toHaveBeenCalledWith({
      eventName: analyticsTypes.ANALYTICS_EVENTS.ICON,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        type: 'Back Icon',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.NACH,
      },
    });
  });

  test('should track analytics for additionalDetails path', async () => {
    mockPathname('/additionalDetails');
    const props = {
      isBackButtonVisible: true,
    };

    render(<OnboardingHeader {...props} />);
    await userEvent.click(screen.getByLabelText('header-back-btn'));

    expect(trackEvent).toHaveBeenCalledWith({
      eventName: analyticsTypes.ANALYTICS_EVENTS.ICON,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        type: 'Back Icon',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.ADDITIONAL_DETAILS,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.TAXATION_AND_COMPLIANCE,
      },
    });
  });
});
