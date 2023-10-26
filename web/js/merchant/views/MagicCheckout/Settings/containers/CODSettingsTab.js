import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { useState, useEffect } from 'react';
import styled from 'styled-components';
import lazy from 'merchant/routes/LazyLoader';
import { Box, Text } from '@razorpay/blade/components';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import ConfirmationModal, {
  DisplayNotificationTxt,
} from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import SettingsToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsToggle';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';
import {
  SWITCH_TEXTS,
  COD_SETTINGS_INFO,
  UPDATE_WOOC_PLUGIN_MSG,
  RCOD_SETTINGS_INFO,
} from 'merchant/views/MagicCheckout/Settings/constants';
import {
  updateEngineConfig,
  fetchConfig,
  validateConfig,
  setEditMode,
} from 'merchant/reducers/magicCheckout/codEngine/action';
import { RCOD_APP_NAME, MAGIC_APP_NAME } from 'merchant/views/MagicCheckout/common/constants';

const CODSettings = lazy(() =>
  import(/* webpackChunkName: "CODSettings" */ 'merchant/views/MagicCheckout/CODSettings'),
);

const StyledPluginUpdateWrapper = styled.div`
  margin: 16px 0;
  padding: 8px;
  border: 1px solid #bd7a03;
  border-left: 3px solid #bd7a03;
  border-radius: 4px;
  background: #fff;
  color: #435775;
`;

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
  const { cod_engine, platform, shop_id, rcodEnabled, rcod = {} } = settings;
  const [codSettings, setCodSettings] = useState(!rcodEnabled ? cod_engine : rcod.enabled);
  const { zones, fee_rules = [], configs = {} } = cod_engine_config;
  useEffect(() => {
    fetchSummary(true, rcodEnabled ? RCOD_APP_NAME : MAGIC_APP_NAME);
  }, []);

  const switchCODSettingsMode = (toggleState) => {
    const modalAction = !toggleState ? 'enabled' : 'disabled';
    const params = {
      cod_engine: !codSettings,
      platform,
    };

    if (platform === PLATFORMS.VALUES.SHOPIFY) {
      params.shop_id = shop_id;

      if (rcodEnabled) {
        delete params.cod_engine;
        params.rcod = {
          enabled: !codSettings,
        };

        if (params.rcod.enabled) {
          params.rcod.configs = {
            cod_engine_type: configs.cod_engine_type,
          };
        }
      }
    }

    const enableEngineConfigPromise = (params) =>
      new Promise((resolve) => {
        if (fee_rules.length && zones.length) {
          updateSettings(params, false);
          updateEngineConfig(params);
          resolve();
        } else {
          if (rcodEnabled) {
            // in rcod, we don't let user add zones
            if (fee_rules.length) {
              updateSettings(params, false);
            }
            params.rcod = true;
          }
          updateEngineConfig(params);
          validateConfig('zones', true);
          validateConfig('fee_rules', true);
          validateConfig('mapping', true);
          setEditMode(true);
          resolve();
        }
      });
    const disableEngineConfigPromise = (params) =>
      new Promise((resolve) => {
        updateSettings(params, false);
        if (rcodEnabled) {
          params.rcod = false;
        }
        updateEngineConfig(params);
        resolve();
      });

    const actionFn =
      params.cod_engine || params.rcod?.enabled
        ? enableEngineConfigPromise
        : disableEngineConfigPromise;
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

  const onToggleClick = () => {
    const modalState = !codSettings ? 'enable' : 'disable';
    const modalAction = () => switchCODSettingsMode(codSettings);
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
          {!rcodEnabled
            ? 'Configure zones, product catalogues, fees, and block unwanted pincodes and mobile numbers.'
            : 'Configure fees and block unwanted pincodes.'}
        </div>
        <div className="cod-settings-toggle">
          <SettingsToggle
            setting={{ label: 'COD as payment option ', value: codSettings }}
            onToggle={onToggleClick}
          />
        </div>
        <Box marginTop="spacing.4">
          <Text type="subdued" size="small">
            {!rcodEnabled ? COD_SETTINGS_INFO : RCOD_SETTINGS_INFO}
          </Text>
          {settings.platform === PLATFORMS.VALUES.WOOCOMMERCE && (
            <StyledPluginUpdateWrapper>{UPDATE_WOOC_PLUGIN_MSG}</StyledPluginUpdateWrapper>
          )}
        </Box>
      </div>
      <div className="cod-settings">
        <ErrorBoundary team={Teams?.MAGIC_CHECKOUT} rank={Ranks.P0} resetOnProps>
          <SuspenseWithLoader type="center">
            <CODSettings isRcod={rcodEnabled} />
          </SuspenseWithLoader>
        </ErrorBoundary>
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
