import React, { useEffect, useCallback } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';

import {
  updatePageView,
  fetchMagicSettings,
  updateMagicSettings,
} from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { showNotification } from 'merchant_common/reducers/notifications';

import Settings from 'merchant/views/MagicCheckout/MagicSettings';
import PlatformSubText from 'merchant/views/MagicCheckout/Settings/components/PlatformSubText';
import { DisplayNotificationTxt } from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import Spinner from 'common/ui/Spinner';

import { analyticsTrack } from 'common/utils/analytics';

import { ACCESS_ROLES, PLATFORMS } from 'merchant/views/MagicCheckout/Settings/constants';
import {
  FETCH_STATUS,
  NESTED_VIEW_TYPE,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';

import { GenericRecord } from 'merchant/views/MagicCheckout/types';

import { StyledHelperText } from './styledComponents';

interface Settings {
  nested_view_type: string;
  status: string;
  has_saved_config: boolean;
  platform: string;
  shop_id?: string;
  domain_url?: string;
  one_click_checkout?: boolean;
  error?: {
    errors: string[];
  };
}

interface PlatformSettingsProps {
  settings: Settings;
  merchantId: string;
  updatePage: (pageType: string) => void;
  updateSettings: (settings: Partial<Settings>) => void;
  fetchSettings: () => void;
  displayNotification: (notification: { type: string; message: React.ReactNode }) => void;
  user: GenericRecord;
}

/**
 * In the new dashboard UI , we dont need to render nestedVerticalTab from platform settings as that would be
 * rendered by default. so instead of altering original file, we created V2 file to maintain new changes.
 */
const PlatformSettings: React.FC<PlatformSettingsProps> = ({
  settings,
  merchantId,
  updatePage,
  updateSettings,
  fetchSettings,
  displayNotification,
  user,
}) => {
  // Old flag and its being used at multiple places , so cant rename it
  // eslint-disable-next-line @typescript-eslint/naming-convention
  const { nested_view_type, status, has_saved_config, platform } = settings;

  const domain = platform === PLATFORMS.SHOPIFY ? settings?.shop_id : settings?.domain_url;

  useEffect(() => {
    if (settings.status === FETCH_STATUS.ERROR) {
      const notifTxt = settings?.error?.errors?.length
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

  if (status === 'loading') {
    return (
      <div className="page-spinner-container">
        <Spinner center={undefined} />
      </div>
    );
  }
  return (
    <div className="platform-settings-container">
      <div className="platform-settings magic-settings-container">
        <div>
          {ACCESS_ROLES.includes(user.role as string) && (
            <div className="padding-16 bg-settings platform-heading-container">
              <div className="font-bold font-20 platform-heading">
                {user.isC360OnboardingCompleted ? 'Checkout360' : 'Platform'} Settings
              </div>
              {user.isC360OnboardingCompleted && (
                <StyledHelperText>
                  Checkout360 activated. Set up COD and other configurations here.
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
          )}
          {nested_view_type === NESTED_VIEW_TYPE.PLATFORM_SELECTION ? <Settings /> : null}
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
