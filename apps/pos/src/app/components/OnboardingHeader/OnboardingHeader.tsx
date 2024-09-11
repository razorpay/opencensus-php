import { Box, ChevronLeftIcon, Heading, IconButton, Text } from '@razorpay/blade/components';
import React from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';

interface OnboardingHeaderProps {
  pageLabel?: string;
  title?: string;
  description?: string;
  isBackButtonVisible?: boolean;
  footer?: JSX.Element;
}

const OnboardingHeader = ({
  title,
  description,
  pageLabel,
  footer,
  isBackButtonVisible,
}: OnboardingHeaderProps): JSX.Element => {
  const navigate = useNavigate();
  const location = useLocation();

  const trackHeaderEvents = () => {
    const path = location.pathname;
    switch (true) {
      case path.includes('deviceSelectionCatalog'):
        trackEvent({
          eventName: analyticsTypes.ANALYTICS_EVENTS.ICON,
          action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
          properties: {
            type: 'Back Icon',
            l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.DEVICE_EXPLORATION,
            l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.POS_PRODUCT_DESCRIPTION,
          },
        });
        break;
      case path.includes('mobileNumberVerify'):
        trackEvent({
          eventName: analyticsTypes.ANALYTICS_EVENTS.ICON,
          action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
          properties: {
            type: 'Back Icon',
            l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.MERCHANT_SIGNUP,
            l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.MOBILE_NUMBER_ENTRY,
          },
        });
        break;
      case path.includes('nachForm'):
        trackEvent({
          eventName: analyticsTypes.ANALYTICS_EVENTS.ICON,
          action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
          properties: {
            type: 'Back Icon',
            l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.PAYMENT_METHOD_AND_SERVICE_SELECTION,
            l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.NACH,
          },
        });
        break;
      case path.includes('additionalDetails'):
        trackEvent({
          eventName: analyticsTypes.ANALYTICS_EVENTS.ICON,
          action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
          properties: {
            type: 'Back Icon',
            l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.ADDITIONAL_DETAILS,
            l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.TAXATION_AND_COMPLIANCE,
          },
        });
        break;
      default:
        break;
    }
  };

  const onBackButtonClick = () => {
    trackHeaderEvents();
    navigate(-1);
  };

  return (
    <Box>
      <Box display="flex" justifyContent="space-between" marginBottom="spacing.0">
        {isBackButtonVisible ? (
          <IconButton
            icon={ChevronLeftIcon}
            size="large"
            onClick={onBackButtonClick}
            accessibilityLabel="header-back-btn"
          />
        ) : null}
        {pageLabel ? (
          <Text color="surface.text.gray.muted" weight="semibold" size="small">
            {pageLabel}
          </Text>
        ) : null}
      </Box>

      <Box>
        {title ? (
          <Heading size="large" color="surface.text.gray.normal" marginTop="spacing.5">
            {title}
          </Heading>
        ) : null}
        {description ? (
          <Text color="surface.text.gray.subtle" marginBottom="spacing.5">
            {description}
          </Text>
        ) : null}
      </Box>
      {footer}
    </Box>
  );
};

export default OnboardingHeader;
