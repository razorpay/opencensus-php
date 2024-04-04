import React, { useEffect } from 'react';
import { Box, ExternalLinkIcon, Link, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { NavLink, Outlet, useLocation } from 'react-router-dom';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { RZPFeatures } from 'merchant/helpers/data';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import RiskAndFraudOnBoarding, {
  getIsRiskAndFraudEnabled,
  getIsAllowedResetRiskAndFraudOnBoarding,
  setRiskAndFraudOnBoardingData,
} from 'merchant/views/RiskAndFraud/OnBoarding';

import QuickGuide, { getRiskAndFraudQuickGuideIsClosed } from './QuickGuide';
import { DOCUMENT_LINK, RiskFraudEntityRoute, RiskFraudPagesMap } from './common/constant';
import { trackEvent } from './common/trackEvents';
import { replaceBusinessName } from './common/utils';
import { StyledTab, TabsHeader } from './components/styled';

const { RISK_ANALYTICS_ROUTE } = RiskFraudEntityRoute;

const Tab = ({ children, to, exact, onClick }) => {
  const { pathname } = useLocation();
  const isActive = pathname === to;
  return (
    <NavLink aria-label={children} data-path={to} to={to} end={exact ?? false} onClick={onClick}>
      <Text variant="body" weight="semibold" size="medium">
        <StyledTab as="span" active={isActive}>
          {children}
        </StyledTab>
      </Text>
    </NavLink>
  );
};

const RiskAndFraud = ({ riskAndFraudProductOnBoarding, handleProductQuickGuide, businessName }) => {
  const { showOnboarding, isQuickGuideOpen, isTour } = riskAndFraudProductOnBoarding;
  const docLink = replaceBusinessName({ str: DOCUMENT_LINK, businessName });

  const initRiskAndFraudOnboarding = () => {
    if (isTour) {
      return;
    }

    const isRiskAndFraudEnabled = getIsRiskAndFraudEnabled();
    let shouldShowOnboarding = !isRiskAndFraudEnabled;

    if (isRiskAndFraudEnabled) {
      shouldShowOnboarding = getIsAllowedResetRiskAndFraudOnBoarding();
    }

    handleProductQuickGuide({
      ...riskAndFraudProductOnBoarding,
      showOnboarding: shouldShowOnboarding,
      isQuickGuideOpen: !getRiskAndFraudQuickGuideIsClosed(),
    });
  };

  const closeOnboarding = () => {
    setRiskAndFraudOnBoardingData(true);

    handleProductQuickGuide({
      ...riskAndFraudProductOnBoarding,
      showOnboarding: false,
    });
  };

  useEffect(() => {
    initRiskAndFraudOnboarding();
  }, []);

  const handleTab = (e) => {
    const path = e.currentTarget.getAttribute('data-path');

    trackEvent({
      objectName: 'Risk and Fraud Tab',
      properties: {
        tabName: RiskFraudPagesMap[path],
        path,
      },
    });
  };

  const handleLink = () => {
    trackEvent({
      objectName: 'Document Link',
      properties: { link: docLink },
    });
  };

  if (showOnboarding) return <RiskAndFraudOnBoarding closeOnboarding={closeOnboarding} />;

  return (
    <Box padding={['spacing.7', 'spacing.6', 'spacing.7', 'spacing.6']}>
      <div className="banner-container">
        {isQuickGuideOpen ? (
          <Box marginBottom="spacing.5">
            <QuickGuide />
          </Box>
        ) : null}
      </div>
      <TabsHeader>
        <Tab to={RISK_ANALYTICS_ROUTE} exact={true} onClick={handleTab}>
          Risk Analytics
        </Tab>
        <Box display="flex" alignItems="center" padding="spacing.4" paddingRight="spacing.7">
          <Link
            href={docLink}
            icon={ExternalLinkIcon}
            iconPosition="right"
            target="_blank"
            rel="noopener noreferer"
            onClick={handleLink}
          >
            Document
          </Link>
        </Box>
      </TabsHeader>
      <Box>
        <ErrorBoundary resetOnProps>
          <Outlet />
        </ErrorBoundary>
      </Box>
    </Box>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  businessName: state.session.org?.business_name,
  riskAndFraudProductOnBoarding: getCurrentProductOnBoardingDetails(
    state,
    RZPFeatures.RISK_AND_FRAUD,
  ),
});

export default connect(mapStateToProps, { handleProductQuickGuide })(RiskAndFraud);
