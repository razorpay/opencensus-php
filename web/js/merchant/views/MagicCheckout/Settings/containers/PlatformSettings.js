import { useEffect, useCallback } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import {
  updatePageView,
  fetchMagicSettings,
} from 'merchant/reducers/magicCheckout/magicSettings/actions';
import Settings from 'merchant/views/MagicCheckout/MagicSettings';
import {
  FETCH_STATUS,
  NESTED_VIEW_TYPE,
  PLATFORMS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import NestedVerticalTab from 'merchant/views/MagicCheckout/Settings/containers/NestedVerticalTab';
import PlatformSubText from 'merchant/views/MagicCheckout/Settings/components/PlatformSubText';
import { showNotification } from 'merchant_common/reducers/notifications';
import Spinner from 'common/ui/Spinner';
import { analyticsTrack } from 'common/utils/analytics';

const PlatformSettings = ({
  settings,
  merchantId,
  updatePage,
  fetchSettings,
  displayNotification,
}) => {
  const { nested_view_type, status, has_saved_config } = settings;

  useEffect(() => {
    if (settings.status === FETCH_STATUS.ERROR) {
      displayNotification({
        type: 'error',
        message: settings.error.errors.length
          ? settings.error.errors[0]
          : 'Something went wrong. Please try again',
      });
    }
  }, [settings.status]);

  useEffect(() => {
    if (fetchSettings) {
      fetchSettings();
    }
  }, [fetchSettings]);

  const getAnalyticsProperties = useCallback(
    (pageType) => {
      let analyticsProperties;
      if (pageType === NESTED_VIEW_TYPE.PLATFORM_SELECTION) {
        analyticsProperties = {
          objectName: '1ccplatformsettingsl0shown',
          actionName: 'render',
          screen: 'platform settings l0',
          properties: { merchant_id: merchantId },
        };
      } else {
        analyticsProperties = {
          objectName: '1ccplatformsettingsl1shown',
          actionName: 'render',
          screen: 'platform settings l1',
          properties: {
            magic_checkout_enabled: settings?.one_click_checkout,
            platform: settings?.platform,
            store_id: settings?.shop_id || null,
            merchant_id: merchantId,
          },
        };
      }

      return analyticsProperties;
    },
    [merchantId, settings],
  );

  const handleUpdatePage = (pageType) => {
    analyticsTrack(getAnalyticsProperties(pageType));
    updatePage(pageType);
  };

  useEffect(() => {
    if (status === FETCH_STATUS.IDLE && has_saved_config) {
      handleUpdatePage(NESTED_VIEW_TYPE.SETTINGS);
    }
  }, [status]);

  const getNestedVerticalTab = () => {
    const { platform, one_click_checkout } = settings;
    if (!(platform === PLATFORMS.VALUES.SHOPIFY && !one_click_checkout)) {
      return <NestedVerticalTab />;
    }
    return null;
  };

  if (status === 'loading') {
    return (
      <div className="page-spinner-container">
        <Spinner />
      </div>
    );
  }
  return (
    <div className="platform-settings-container">
      <div className="platform-settings magic-settings-container">
        <div>
          <div className="padding-16 bg-settings platform-heading-container">
            <div className="font-bold font-20 platform-heading">Platform Settings</div>
            <PlatformSubText {...settings} updatePage={handleUpdatePage} merchantId={merchantId} />
          </div>
          {nested_view_type === NESTED_VIEW_TYPE.PLATFORM_SELECTION ? (
            <Settings />
          ) : (
            getNestedVerticalTab()
          )}
        </div>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
  merchantId: state.config?.config?.id,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updatePage: updatePageView,
      fetchSettings: fetchMagicSettings,
      displayNotification: showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(PlatformSettings);
