import React, { useEffect } from 'react';
import { Box, Text } from '@razorpay/blade/components';
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
import { RiskFraudEntityRoute } from './common/constant';
import { StyledTab, TabsHeader } from './components/styled';

const { RISK_ANALYTICS_ROUTE } = RiskFraudEntityRoute;

const Tab = ({ children, to, exact }) => {
  const { pathname } = useLocation();
  const isActive = pathname === to;
  return (
    <NavLink aria-label={children} to={to} end={exact ?? false} key={to}>
      <Text variant="body" weight="bold" size="medium">
        <StyledTab as="span" active={isActive}>
          {children}
        </StyledTab>
      </Text>
    </NavLink>
  );
};

const RiskAndFraud = ({ riskAndFraudProductOnBoarding, handleProductQuickGuide }) => {
  const { showOnboarding, isQuickGuideOpen, isTour } = riskAndFraudProductOnBoarding;

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
        <Tab to={RISK_ANALYTICS_ROUTE} exact={true}>
          Risk Analytics
        </Tab>
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
  riskAndFraudProductOnBoarding: getCurrentProductOnBoardingDetails(
    state,
    RZPFeatures.RISK_AND_FRAUD,
  ),
});

export default connect(mapStateToProps, { handleProductQuickGuide })(RiskAndFraud);
