import { useEffect, useCallback } from 'react';
import Card from 'merchant/views/MagicCheckout/MagicSettings/components/shopify/Card';
import Form from 'merchant/views/MagicCheckout/MagicSettings/components/shopify/Form';
import {
  FETCH_STATUS,
  ANALYTICS_FORM,
  ANALYTICS_CARD,
  ANALYTICS_SETTINGS,
  SHOPIFY_ANALYTICS_SETTINGS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { getInitialSettings } from 'merchant/views/MagicCheckout/MagicSettings/containers/helpers';

const AnalyticsWrapper = ({
  settings,
  showFormView,
  setCurrentView,
  analyticSettings,
  setAnalyticSettings,
}) => {
  const { nestedTabsStatus, one_cc_ga_analytics, one_cc_fb_analytics } = settings;

  useEffect(() => {
    setAnalyticSettings((prevSettings) => {
      const tempAnalyticSettings = getInitialSettings(SHOPIFY_ANALYTICS_SETTINGS, prevSettings);
      tempAnalyticSettings.forEach((settingItem) => {
        settingItem.value = settings[settingItem.key];
      });

      return tempAnalyticSettings;
    });
    if (nestedTabsStatus !== FETCH_STATUS.LOADING) {
      setCurrentView(ANALYTICS_CARD);
    } else {
      setCurrentView(ANALYTICS_FORM);
    }
  }, [
    one_cc_ga_analytics,
    one_cc_fb_analytics,
    setAnalyticSettings,
    nestedTabsStatus,
    settings,
    setCurrentView,
  ]);

  const onToggleAnalytics = useCallback((checked, label) => {
    setAnalyticSettings((prevSettings) => {
      const tempAnalyticSettings = [...prevSettings];
      tempAnalyticSettings.forEach((setting) => {
        if (setting.label === label) {
          setting.value = checked;
        }
      });

      return tempAnalyticSettings;
    });
  }, []);

  const switchToEdit = () => setCurrentView(ANALYTICS_FORM);
  return (
    <>
      {showFormView ? (
        <Form
          formTitle={ANALYTICS_SETTINGS}
          formItems={analyticSettings}
          onToggle={onToggleAnalytics}
        />
      ) : (
        <Card
          settings={settings}
          switchToEdit={switchToEdit}
          cardTitle={ANALYTICS_SETTINGS}
          cardItems={SHOPIFY_ANALYTICS_SETTINGS}
          extraClass="analytics-card"
        />
      )}
    </>
  );
};

export default AnalyticsWrapper;
