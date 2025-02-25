import React from 'react';
import styled from 'styled-components';
import { analyticsTrack } from '@libs/shared-utils';
import type { RazorpayUser } from '@libs/shared-types';
import { matchPath, useNavigate } from 'react-router-dom';
import { Card, CardBody, Box, ArrowRightIcon, Text, ProgressBar } from '@razorpay/blade/components';
import { ROUTE_REG, BASE_ROUTES } from './href';
import { renderWidget } from '../utils';
import { subWidgetKeyToComponentMapping } from './mapping';
import { NavigationLink } from './types';
import { useStore } from '@federated/apps/shell/commonStore';

export const BoxSizingReset = styled.div`
  * {
    box-sizing: border-box !important;
  }
`;

export const getSubWidget = (widget) =>
  renderWidget({
    widget,
    widgetMapping: subWidgetKeyToComponentMapping,
  });

// get the href value from leaf node, in case of L2 or L3 levels of navigation
export const getHref = (link: NavigationLink): string => {
  if (link.components) {
    return getHref(link.components[0]);
  }
  return BASE_ROUTES[link.actions?.action ?? 'home'];
};

/* Activation banner */
export const ONBOARDING_STEPS_URL = '/onboarding/steps';
export const KYC_URL = '/kyc';
export const ACTIVATION_URL = '/activation';

export const getNCUrlOnEasyOrPhantom = ({ user }: RazorpayUser): string => {
  const EASY_ONBOARDING_URL = window.EASY_ONBOARDING_URL;

  const EASY_DASHBOARD_NC_LANDING_URL = `${EASY_ONBOARDING_URL}/onboarding/needs-clarification`;
  const PHANTOM_NC_LANDING_URL = `${EASY_ONBOARDING_URL}/sub-merchant/onboarding/needs-clarification`;
  return user?.signup_campaign === 'phantom_onboarding'
    ? PHANTOM_NC_LANDING_URL
    : EASY_DASHBOARD_NC_LANDING_URL;
};

export const checkEligibilityForFeeBasedGating = ({
  fee_based_gating,
  activation_status,
  activation_form_milestone,
}: RazorpayUser) =>
  Boolean(fee_based_gating?.is_eligible) &&
  activation_status === null &&
  activation_form_milestone === 'L2';

export const handleFeeBasedGatingNavigation = (trackProps = {}) => {
  analyticsTrack({
    objectName: 'Fee Based Gating',
    actionName: 'Redirect',
    screen: 'home page',
    properties: {
      ctaClicked: 'Get KYC Verified',
      ...trackProps,
    },
  });
  const feeBasedGatingOnEasyUrl = `${window.EASY_ONBOARDING_URL}/onboarding/fee-payment`;
  window.open(feeBasedGatingOnEasyUrl, '_self', 'noopener');
};

export const redirectToEasyAfter1sec = () => {
  setTimeout(() => {
    window.open(window.EASY_ONBOARDING_URL, '_self', 'noopener');
  }, 1000);
};

export const isOnboardingV2Enabled = ({ experiments }: RazorpayUser) =>
  experiments?.onboarding_v2.result === 'on';

export const isActivationFormFullView = ({
  partner_type,
  isSubMerchant,
  isOrgRZP,
}: RazorpayUser) => {
  const query = new URLSearchParams(window.location.search);
  const isSourceRX = Boolean(query && query.get('merchant') === 'x');

  return isSourceRX || partner_type || isSubMerchant ? false : false && isOrgRZP;
};

export const ActivationBanner = ({ activationProgress, isNCEligible = false }) => {
  const user = useStore(({ session }) => session.user);

  const navigate = useNavigate();

  const handleActivationClick = () => {
    const isSignupWithEasyOnboarding = user.user?.signup_campaign === 'easy_onboarding';

    if (isNCEligible && user.activation_status === 'needs_clarification') {
      analyticsTrack({
        objectName: 'NC Easy',
        actionName: 'Redirect',
        screen: 'home page',
        properties: {
          ctaLabel: 'Account Activation',
          ctaLocation: 'LHS_Nav_Bar_v2',
          ncCount: user?.kyc_clarification_reasons?.nc_count,
        },
        includeScreenResolution: true,
      });
      const needsClarificationOnEasyUrl = getNCUrlOnEasyOrPhantom(user);
      window.open(needsClarificationOnEasyUrl, '_self', 'noopener');
    } else if (checkEligibilityForFeeBasedGating(user)) {
      handleFeeBasedGatingNavigation({ ctaLocation: 'Sidebar' });
    } else if (isSignupWithEasyOnboarding) {
      analyticsTrack({
        objectName: 'redirect to easy-dashboard CTA',
        actionName: 'Redirect',
        screen: 'home page',
        properties: {
          'CTA Label': 'Account Activation',
        },
      });
      redirectToEasyAfter1sec();
      // TODO : How to do isMobile check?
    } else if (isOnboardingV2Enabled(user) /*&& isMobile*/) {
      navigate(ONBOARDING_STEPS_URL);
    } else if (isActivationFormFullView(user)) {
      navigate(KYC_URL);
    } else {
      navigate(ACTIVATION_URL);
    }
  };

  return (
    <BoxSizingReset>
      <Card onClick={handleActivationClick} padding="spacing.4" elevation="none" width="100%">
        <CardBody>
          <Box display="flex" justifyContent="space-between" marginBottom="spacing.2">
            <Text size="medium" weight="semibold">
              {activationProgress === 100 ? 'Activation complete' : 'Activation Pending'}
            </Text>
            <Box>
              <ArrowRightIcon />
            </Box>
          </Box>
          {activationProgress === 100 ? (
            <Text color="surface.text.gray.subtle" size="small">
              Personalise your account now
            </Text>
          ) : (
            <ProgressBar label="Progress" showPercentage={true} value={50} />
          )}
        </CardBody>
      </Card>
    </BoxSizingReset>
  );
};
/* Activation banner */

/* Active state for navigation items */
export const isNavigationItemActive = (
  navigationItem: NavigationLink,
  pathname: string,
): boolean => {
  if (navigationItem.components) {
    return navigationItem.components.some((navigation) =>
      isNavigationItemActive(navigation, pathname),
    );
  }

  const isRouteRegexActive = ROUTE_REG[navigationItem.actions?.action ?? '']
    ? ROUTE_REG[navigationItem.actions?.action ?? ''].test(pathname)
    : false;

  return (
    Boolean(matchPath(BASE_ROUTES[navigationItem.actions?.action ?? 'home'], pathname)) ||
    Boolean(isRouteRegexActive)
  );
};
/* Active state for navigation items */
