import React, { Suspense } from 'react';
import { Route, NavLink, Navigate, Routes } from 'react-router-dom';
import { useI18Service } from 'common/i18';
import { useSplitzService } from 'common/splitz';
import TestModeBanner from 'merchant/components/TestModeBanner';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import DashboardBanner from 'common/ui/DashboardBanner';
import Breadcrumb from 'common/components/Breadcrumb';
import {
  StyledConfiguration,
  StyledDivider,
  StyledTabContentContainer,
  StyledHeader,
  StyledTabContainer,
} from 'merchant/views/AccountAndSettings/styled';
import {
  isWhatsappNotificationEnabled,
  isSmsNotificationEnabled,
  isEmailNotificationEnabled,
} from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import { connect } from 'react-redux';
import {
  accountAndSettingsLink,
  ROUTE_MAP,
} from 'merchant/views/AccountAndSettings/constants/constants';
import ShowWhen, { RouteGuard } from 'merchant/components/ShowWhen';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import Loader from 'common/components/Loader';
import { ExtraConfig } from 'merchant/components/SidebarV2/utils/Products';
import DocsLink from 'merchant/components/DocsLink';
import { Box } from 'merchant_common/views/Reports/components';

const NotificationSettings = ({ user, location: { pathname } }): JSX.Element | null => {
  const { abExperiments } = useSplitzService();
  const { isConfigTagEnabled } = useI18Service();
  const extraConfig: ExtraConfig = { abExperiments, isConfigTagEnabled };
  if (!user.isAccountAndSettingsRevampEnabled) {
    switch (pathname) {
      case ROUTES_INFO.EMAIL_NOTIFICATIONS:
      case ROUTES_INFO.SMS_NOTIFICATIONS:
      case ROUTES_INFO.WHATSAPP_NOTIFICATIONS:
        return <Navigate to="/config" replace />;
      default:
        return <Navigate to="/dashboard" replace />;
    }
  }

  const getRefRoute = (routePath: string) => {
    return `${routePath.replace('/notification-settings/', '')}/*`;
  };

  return (
    <StyledTabContainer>
      <div className="banner-container">
        <DashboardBanner />
      </div>
      <div className="tabbed-container">
        <Breadcrumb
          items={[
            accountAndSettingsLink,
            {
              label: ROUTE_MAP[pathname],
              link: pathname,
            },
          ]}
        />
        <StyledHeader className="scrollable-tab-header">
          <ShowWhen additionalCondition={isEmailNotificationEnabled}>
            <NavLink to={ROUTES_INFO.EMAIL_NOTIFICATIONS}>Email</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={isSmsNotificationEnabled}>
            <NavLink to={ROUTES_INFO.SMS_NOTIFICATIONS}>SMS</NavLink>
          </ShowWhen>
          <ShowWhen
            additionalCondition={(user) => isWhatsappNotificationEnabled(user, extraConfig)}
          >
            <NavLink to={ROUTES_INFO.WHATSAPP_NOTIFICATIONS}>WhatsApp</NavLink>
          </ShowWhen>
          <Box display="inline">
            <DocsLink title="Documentation" isTab shouldApplyLineHeight shouldFloatRight />
          </Box>
        </StyledHeader>
        <TestModeBanner />
        <ErrorBoundary resetOnProps>
          <Suspense fallback={<Loader />}>
            <StyledDivider>
              <StyledTabContentContainer className="content">
                <Routes>
                  <Route
                    path={getRefRoute(ROUTES_INFO.EMAIL_NOTIFICATIONS)}
                    element={
                      <RouteGuard>
                        <StyledConfiguration showEmailNotifications />
                      </RouteGuard>
                    }
                  />
                  <Route
                    path={getRefRoute(ROUTES_INFO.SMS_NOTIFICATIONS)}
                    element={
                      <RouteGuard>
                        <StyledConfiguration showSmsNotifications />
                      </RouteGuard>
                    }
                  />
                  <Route
                    path={getRefRoute(ROUTES_INFO.WHATSAPP_NOTIFICATIONS)}
                    element={
                      <RouteGuard>
                        <StyledConfiguration showWhatsappNotifications />
                      </RouteGuard>
                    }
                  />
                </Routes>
              </StyledTabContentContainer>
            </StyledDivider>
          </Suspense>
        </ErrorBoundary>
      </div>
    </StyledTabContainer>
  );
};

export default connect((state) => ({
  user: state.session.user,
}))(NotificationSettings);
