import React, { useEffect, useState } from 'react';
import { Heading, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { AnyAction, Dispatch, bindActionCreators } from 'redux';
import styled from 'styled-components';

import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import Spinner from 'common/ui/Spinner';
import { getURLQueryParams } from 'common/utils/rzp-utils';
import {
  fetchConfigs,
  resetAnalyticsSettings,
} from 'merchant/reducers/magicCheckout/analyticsSettings/actions';
import {
  ANALYTICS_SETTINGS_ROUTES,
  NOTIFICATION_TEXTS,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/constants';
import {
  AnalyticsSettingsPropsType,
  NavContentPropType,
  NavItemPropType,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/types';
import { showNotification } from 'merchant_common/reducers/notifications';

const AnalyticsSettingContainer = styled.div`
  padding: 16px;
`;

const TabNavItem = ({ id, title, activeTab, setActiveTab }: NavItemPropType): JSX.Element => {
  const handleClick = () => {
    setActiveTab(id);
  };

  return (
    <li
      onClick={handleClick}
      className={activeTab === id ? 'active scrollable-tab-header' : 'scrollable-tab-header'}
    >
      {title}
    </li>
  );
};

const TabContent = ({
  id,
  activeTab,
  component: Component,
  className,
  analyticsSettingsConfigs,
}: NavContentPropType): JSX.Element | null => {
  return activeTab === id ? (
    <div className={`tabContent ${className}`}>
      {<Component analyticsSettingsConfigs={analyticsSettingsConfigs} />}
    </div>
  ) : null;
};

const AnalyticsSettings = (props: AnalyticsSettingsPropsType): JSX.Element => {
  const integrationPlatform = getURLQueryParams(window.location.search)?.platform;

  const [activeTab, setActiveTab] = useState<string>(integrationPlatform || 'google-analytics');

  const {
    platform,
    fetchAnalyticsSettings,
    analyticsSettingsConfigs,
    showNotification,
    resetConfigs,
  } = props;

  const { isLoading } = analyticsSettingsConfigs;

  useEffect(() => {
    fetchAnalyticsSettings().catch(() => {
      showNotification({
        type: 'error',
        message: NOTIFICATION_TEXTS.error,
      });
    });

    return () => resetConfigs();
  }, []);

  if (isLoading.authConfigs) {
    return (
      <div className="spinner-container">
        <Spinner center />
      </div>
    );
  }

  return (
    <ErrorBoundary team={Teams?.MAGIC_CHECKOUT} rank={Ranks.P0} resetOnProps>
      <SuspenseWithLoader type="center">
        <AnalyticsSettingContainer>
          <Heading size="medium">Analytics Settings</Heading>
          <Text color="surface.text.gray.muted">
            Boost conversion and take better decisions by Integrating Google Analytics, Google Ads
            and Facebook Ads with us.
          </Text>
          <div className="tabs margin-t-16">
            <div className="tabbed-container">
              <header>
                {ANALYTICS_SETTINGS_ROUTES.map((item) => {
                  if (item.condition && !item.condition(platform)) {
                    return null;
                  }
                  return (
                    <TabNavItem
                      key={item.id}
                      id={item.id}
                      title={item.title}
                      activeTab={activeTab}
                      setActiveTab={setActiveTab}
                    />
                  );
                })}
              </header>
              <div>
                {ANALYTICS_SETTINGS_ROUTES.map((item) => (
                  <TabContent
                    key={item.id}
                    id={item.id}
                    activeTab={activeTab}
                    className={item.className}
                    component={item.Component}
                    analyticsSettingsConfigs={analyticsSettingsConfigs}
                  />
                ))}
              </div>
            </div>
          </div>
        </AnalyticsSettingContainer>
      </SuspenseWithLoader>
    </ErrorBoundary>
  );
};

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) =>
  bindActionCreators(
    {
      fetchAnalyticsSettings: fetchConfigs,
      showNotification,
      resetConfigs: resetAnalyticsSettings,
    },
    dispatch,
  );

const mapStateToProps = (state: Record<string, any>) => ({
  platform: state.magic_settings.platform,
  analyticsSettingsConfigs: state.magicAnalyticsSettings,
});

export default connect(mapStateToProps, mapDispatchToProps)(AnalyticsSettings);
