import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { useState, useEffect } from 'react';
import lazy from 'merchant/routes/LazyLoader';
import { Box, Text } from '@razorpay/blade/components';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import ConfirmationModal, {
  DisplayNotificationTxt,
} from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import SettingsToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsToggle';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { SWITCH_TEXTS, COD_SETTINGS_INFO } from 'merchant/views/MagicCheckout/Settings/constants';
import {
  updateEngineConfig,
  fetchConfig,
  validateConfig,
  setEditMode,
} from 'merchant/reducers/magicCheckout/codEngine/action';

const CODSettings = lazy(() =>
  import(/* webpackChunkName: "CODSettings" */ 'merchant/views/MagicCheckout/CODSettings'),
);

const CODSettingsTab = ({
  settings,
  cod_engine_config,
  updateSettings,
  updateEngineConfig,
  openModal,
  closeModal,
  showNotification,
  fetchSummary,
  setEditMode,
  validateConfig,
}) => {
  const { cod_engine, platform, shop_id } = settings;
  const [codSettings, setCodSettings] = useState(cod_engine);
  const { zones, fee_rules } = cod_engine_config;
  useEffect(() => {
    fetchSummary();
  }, []);

  const switchCODSettingsMode = (toggleState) => {
    const modalAction = toggleState ? 'enabled' : 'disabled';
    const params = {
      cod_engine: !codSettings,
      platform,
    };

    if (platform === PLATFORMS.VALUES.SHOPIFY) {
      params.shop_id = shop_id;
    }

    const enableEngineConfigPromise = (params) =>
      new Promise((resolve) => {
        updateEngineConfig(params);
        validateConfig('zones', true);
        validateConfig('fee_rules', true);
        if (fee_rules.length === 0 || zones.length === 0) {
          setEditMode(true);
        }
        resolve();
      });
    const disableEngineConfigPromise = (params) =>
      new Promise((resolve) => {
        updateSettings(params, false);
        updateEngineConfig(params);
        resolve();
      });
    const actionFn = params.cod_engine ? enableEngineConfigPromise : disableEngineConfigPromise;
    actionFn(params)
      .then(() => {
        showNotification({
          type: 'success',
          message: () => (
            <DisplayNotificationTxt notificationTxt={`COD Settings ${modalAction} successfully`} />
          ),
        });

        setCodSettings((prevState) => {
          return !prevState;
        });
      })
      .finally(() => {
        closeModal();
      });
  };

  const onToggleClick = (toggleState) => {
    const modalState = toggleState ? 'enable' : 'disable';
    const modalAction = () => switchCODSettingsMode(toggleState);
    const { header, desc, subText, secondaryCtaLabel, primaryCtaLabel } =
      SWITCH_TEXTS[modalState].codSettings;

    openModal({
      size: 'small',
      className: `magicToggleConfirmationModal`,
      component: (
        <ConfirmationModal
          header={header}
          subText={subText}
          desc={desc}
          affirmativeLabel={primaryCtaLabel}
          abortLabel={secondaryCtaLabel}
          onAffirm={modalAction}
        />
      ),
    });
  };
  return (
    <div className="magic-cod">
      <div className="header-wrapper">
        <div className="font-20 font-bold heading">Cash on delivery settings </div>
        <div className="font-14 subtext">
          Configure zones, product catalogues, fees, and block unwanted pincodes and mobile numbers.
        </div>
        <div className="cod-settings-toggle">
          <SettingsToggle
            setting={{ label: 'COD as payment option ', value: codSettings }}
            onToggle={onToggleClick}
          />
        </div>
        <Box marginTop="spacing.4">
          <Text type="subdued" size="small">
            {COD_SETTINGS_INFO}
          </Text>
        </Box>
      </div>
      <div className="cod-settings">
        <SuspenseWithLoader type="center">
          <CODSettings />
        </SuspenseWithLoader>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
  merchantId: state.config?.config?.id,
  cod_engine_config: state.magicCODEngine,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateSettings: updateMagicSettings,
      fetchSummary: fetchConfig,
      setEditMode,
      validateConfig,
      updateEngineConfig,
      openModal,
      closeModal,
      showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(CODSettingsTab);
