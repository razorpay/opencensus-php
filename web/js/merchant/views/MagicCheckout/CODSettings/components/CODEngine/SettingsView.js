import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import Input from 'common/new-ui/Input';
import ConfirmationModal from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import SettingsLabel from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/SettingsLabel';
import BasicFlow from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/BasicFlow';
import AdvancedFlow from './AdvancedFlow';

import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import {
  fetchConfig,
  updateEngineConfig,
  clearFeeRules,
} from 'merchant/reducers/magicCheckout/codEngine/action';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

import {
  SETTINGS_OPTIONS,
  POPOVER_CONTENT,
  COD_ENGINES,
  COD_ENGINE_TYPES,
} from 'merchant/views/MagicCheckout/CODSettings/constants';
import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';

function SettingsView({
  configs,
  settings,
  updateSettings,
  updateEngineConfig,
  fetchConfig,
  openModal,
  closeModal,
  clearFeeRules,
}) {
  const { engine } = configs;
  const { platform, shop_id } = settings;

  const updateConfigAndRefetchSummary = () => {
    const params = {
      platform,
      cod_engine_type: COD_ENGINE_TYPES.SLAB_ELIGIBILITY,
    };
    if (platform === PLATFORMS.VALUES.SHOPIFY) {
      params.shop_id = shop_id;
    }
    Promise.all([
      updateSettings(params, false),
      clearFeeRules({
        fee_type: 'cod_fee',
      }),
    ])
      .then(() => {
        fetchConfig();
      })
      .then(() => {
        console.log(configs);
        updateEngineConfig({
          engine: COD_ENGINES.BASIC,
          cod_engine_type: COD_ENGINE_TYPES.SLAB_ELIGIBILITY,
        });
      })
      .then(() => closeModal());
  };

  const onSettingTypeChange = ({ target }) => {
    const { value } = target;
    if (value === COD_ENGINES.BASIC) {
      openModal({
        size: 'small',
        className: `magicToggleConfirmationModal`,
        component: (
          <ConfirmationModal
            header="Change settings?"
            desc="Are you sure you want to change the engine type. This will delete all the configurations and fee rules created"
            affirmativeLabel="Yes"
            abortLabel="No"
            onAffirm={() => updateConfigAndRefetchSummary()}
          />
        ),
      });
      return;
    }
    updateEngineConfig({
      engine: value,
      cod_engine_type: COD_ENGINE_TYPES.LOCATION,
    });
  };
  return (
    <div className="settings-view" data-testid="settings-view">
      <div className="cod-setting-item">
        <SettingsLabel
          value="Type of setting"
          required
          popoverContent={POPOVER_CONTENT.setting_type}
        />
        <Input.Select
          value={engine || {}}
          size="small"
          name="engine"
          options={SETTINGS_OPTIONS}
          onChange={onSettingTypeChange}
          className="settings-select"
        />
      </div>
      <BasicFlow />
      {engine === COD_ENGINES.ADVANCED && <AdvancedFlow />}
    </div>
  );
}
const mapStateToProps = (state) => ({
  settings: state.magic_settings,
  configs: state.magicCODEngine.configs,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
      closeModal,
      clearFeeRules,
      fetchConfig,
      updateEngineConfig,
      updateSettings: updateMagicSettings,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(SettingsView);
