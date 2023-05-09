import React, { Suspense } from 'react';
import { Route, NavLink, Redirect } from 'react-router-dom';
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
import ShowWhen from 'merchant/components/ShowWhen';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import Loader from 'common/components/Loader';

const NotificationSettings = ({ user, location: { pathname } }): JSX.Element | null => {
  if (!user.isAccountAndSettingsRevampEnabled) {
    switch (pathname) {
      case ROUTES_INFO.EMAIL_NOTIFICATIONS:
      case ROUTES_INFO.SMS_NOTIFICATIONS:
      case ROUTES_INFO.WHATSAPP_NOTIFICATIONS:
        return <Redirect to="/config" />;
      default:
        return <Redirect to="/dashboard" />;
    }
  }

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
          <ShowWhen additionalCondition={isWhatsappNotificationEnabled}>
            <NavLink to={ROUTES_INFO.WHATSAPP_NOTIFICATIONS}>WhatsApp</NavLink>
          </ShowWhen>
        </StyledHeader>
        <TestModeBanner />
        <ErrorBoundary resetOnProps>
          <Suspense fallback={<Loader />}>
            <StyledDivider>
              <StyledTabContentContainer className="content">
                <Route
                  path={ROUTES_INFO.EMAIL_NOTIFICATIONS}
                  render={(props) => <StyledConfiguration {...props} showEmailNotifications />}
                />
                <Route
                  path={ROUTES_INFO.SMS_NOTIFICATIONS}
                  render={(props) => <StyledConfiguration {...props} showSmsNotifications />}
                />
                <Route
                  path={ROUTES_INFO.WHATSAPP_NOTIFICATIONS}
                  render={(props) => <StyledConfiguration {...props} showWhatsappNotifications />}
                />
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
