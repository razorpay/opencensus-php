import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import { bindActionCreators } from 'redux';
import { useCallback, useEffect, useState } from 'react';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { Settings } from 'merchant/views/MagicCheckout/MagicSettings/components/Settings';
import { fetchMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import SwitchPlatformModal from 'merchant/views/MagicCheckout/MagicSettings/components/SwitchPlatformModal';
import { PLATFORMS, FETCH_STATUS } from 'merchant/views/MagicCheckout/MagicSettings/constants';

const MagicSettings = ({ fetchSettings, settings, displayNotification, openModal, closeModal }) => {
  const [platform, setPlatform] = useState(PLATFORMS.WOOCOMMERCE);

  useEffect(() => setPlatform(settings.platform), [settings.platform]);

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

  const onConfirm = useCallback(
    (value) => () => {
      setPlatform(value);
      closeModal();
    },
    [closeModal],
  );

  const onPlatformChange = useCallback(
    (e) => {
      if (e?.target?.value !== settings.platform && settings.has_saved_config) {
        openModal({
          size: 'medium',
          component: (
            <SwitchPlatformModal
              onConfirm={onConfirm(e?.target?.value)}
              onCancel={closeModal}
              platform={e?.target?.value}
            />
          ),
        });
        return;
      }
      setPlatform(e?.target?.value);
    },
    [settings, openModal, closeModal, onConfirm],
  );

  return (
    <div className="content-wrapper no-padding display-flex magic-settings-container">
      <div className="selection-container">
        <label>Please Select Platform</label>
        <Input.Select
          name="platform"
          className="select-platform-dropdown"
          value={platform}
          options={[
            { label: PLATFORMS.LABELS.WOOCOMMERCE, name: PLATFORMS.VALUES.WOOCOMMERCE },
            { label: PLATFORMS.LABELS.SHOPIFY, name: PLATFORMS.VALUES.SHOPIFY },
            { label: PLATFORMS.LABELS.NATIVE, name: PLATFORMS.VALUES.NATIVE },
          ]}
          onChange={onPlatformChange}
        />
      </div>
      <div className="flex--1 platform-settings-container">
        <Settings settings={settings} platform={platform} />
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
      ...ModalActions,
      fetchSettings: fetchMagicSettings,
      displayNotification: showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(MagicSettings);
