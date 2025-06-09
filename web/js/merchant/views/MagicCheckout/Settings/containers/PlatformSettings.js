import { useEffect, useCallback } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import {
  updatePageView,
  fetchMagicSettings,
  updateMagicSettings,
} from 'merchant/reducers/magicCheckout/magicSettings/actions';
import Settings from 'merchant/views/MagicCheckout/MagicSettings';
import {
  FETCH_STATUS,
  NESTED_VIEW_TYPE,
  PLATFORMS,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import NestedVerticalTab from 'merchant/views/MagicCheckout/Settings/containers/NestedVerticalTab';
import PlatformSubText from 'merchant/views/MagicCheckout/Settings/components/PlatformSubText';
import { DisplayNotificationTxt } from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import { showNotification } from 'merchant_common/reducers/notifications';
import Spinner from 'common/ui/Spinner';
import { analyticsTrack } from 'common/utils/analytics';
import { ACCESS_ROLES } from 'merchant/views/MagicCheckout/Settings/constants';
import { StyledHelperText } from './styledComponents';

const PlatformSettings = ({
  settings,
  merchantId,
  updatePage,
  updateSettings,
  fetchSettings,
  displayNotification,
  user,
  path,
}) => {
  const { nested_view_type, status, has_saved_config, platform } = settings;

  const domain = platform === 'shopify' ? settings?.shop_id : settings?.domain_url;

  useEffect(() => {
    if (settings.status === FETCH_STATUS.ERROR) {
      const notifTxt = settings.error.errors.length
        ? settings.error.errors[0]
        : 'Something went wrong. Please try again';
      displayNotification({
        type: 'error',
        message: <DisplayNotificationTxt notificationTxt={notifTxt} />,
      });
    }
  }, [settings.status]);

  useEffect(() => {
    if (fetchSettings) {
      fetchSettings();
    }
  }, [fetchSettings, platform]);

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
            store_id: domain || null,
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

  const getNestedVerticalTab = (path) => {
    const { one_click_checkout = true } = settings;
    if (platform === PLATFORMS.VALUES.NATIVE || one_click_checkout) {
      return <NestedVerticalTab path={path} />;
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
          {ACCESS_ROLES.includes(user.role) ? (
            <div className="padding-16 bg-settings platform-heading-container">
              <div className="font-bold font-20 platform-heading">
                {user.isC360OnboardingCompleted
                  ? 'Magic Checkout'
                  : 'Platform'}{' '}
                Settings
              </div>
              {user.isC360OnboardingCompleted && (
                <StyledHelperText>
                  Magic Checkout activated. Set up COD and other configurations here.
                </StyledHelperText>
              )}
              <PlatformSubText
                {...settings}
                updatePage={handleUpdatePage}
                updateSettings={updateSettings}
                merchantId={merchantId}
                user={user}
              />
            </div>
          ) : null}
          {nested_view_type === NESTED_VIEW_TYPE.PLATFORM_SELECTION ? (
            <Settings />
          ) : (
            getNestedVerticalTab(path)
          )}
        </div>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
  merchantId: state.config?.config?.id,
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updatePage: updatePageView,
      fetchSettings: fetchMagicSettings,
      displayNotification: showNotification,
      updateSettings: updateMagicSettings,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(PlatformSettings);
