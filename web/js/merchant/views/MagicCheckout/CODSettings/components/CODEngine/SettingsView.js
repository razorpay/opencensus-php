import React from 'react';
import {
  SETTINGS_OPTIONS,
  POPOVER_CONTENT,
  COD_ENGINES,
} from 'merchant/views/MagicCheckout/CODSettings/constants';
import Input from 'common/new-ui/Input';
import SettingsLabel from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/SettingsLabel';
import BasicFlow from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/BasicFlow';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { updateEngineConfig } from 'merchant/reducers/magicCheckout/codEngine/action';

function SettingsView({ configs, updateEngineConfig }) {
  const { engine } = configs;
  const onSettingTypeChange = ({ target }) => {
    const { value, name } = target;
    updateEngineConfig({
      [name]: value,
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
      {engine === COD_ENGINES.BASIC && <BasicFlow />}
    </div>
  );
}
const mapStateToProps = (state) => ({
  configs: state.magicCODEngine.configs,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateEngineConfig,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(SettingsView);
