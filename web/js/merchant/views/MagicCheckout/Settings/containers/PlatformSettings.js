import { useEffect } from 'react';
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
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import NestedVerticalTab from 'merchant/views/MagicCheckout/Settings/containers/NestedVerticalTab';
import PlatformSubText from 'merchant/views/MagicCheckout/Settings/components/PlatformSubText';
import { showNotification } from 'merchant_common/reducers/notifications';
import Spinner from 'common/ui/Spinner';

const PlatformSettings = ({ settings, updatePage, fetchSettings, displayNotification }) => {
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

  useEffect(() => {
    if (status === FETCH_STATUS.IDLE && has_saved_config) {
      updatePage(NESTED_VIEW_TYPE.SETTINGS);
    }
  }, [status]);

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
            <PlatformSubText {...settings} updatePage={updatePage} />
          </div>
          {nested_view_type === NESTED_VIEW_TYPE.PLATFORM_SELECTION ? (
            <Settings />
          ) : (
            <NestedVerticalTab />
          )}
        </div>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
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
