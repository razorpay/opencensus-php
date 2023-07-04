import React from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import { Button, Heading, Text } from '@razorpay/blade/components';
import Spinner from 'common/ui/Spinner';

import EmptyView from 'merchant/views/MagicCheckout/CODSettings/EmptyView';
import PreviewView from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/PreviewView';
import SettingsView from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/SettingsView';
import { openModal } from 'merchant_common/reducers/modals';
import { validateConfig, setEditMode } from 'merchant/reducers/magicCheckout/codEngine/action';
import ConfirmSettings from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/ConfirmSettings';

function CODEngine(props) {
  const { codEngineConfig, openModal, validateConfig, setEditMode } = props;
  const { configs, editMode } = codEngineConfig;

  const handleSave = () => {
    let hasError = false;
    if (codEngineConfig.zones.length === 0) {
      validateConfig('zones', false);
      hasError = true;
    }
    if (codEngineConfig.fee_rules.length === 0) {
      validateConfig('fee_rules', false);
      hasError = true;
    }
    if (hasError) return;

    openModal({
      size: 'small',
      className: `magicToggleConfirmationModal`,
      component: <ConfirmSettings />,
    });
  };

  if (!configs.cod_engine) return <EmptyView viewName="COD settings" />;

  if (codEngineConfig.loading)
    return (
      <div className="page-spinner-container">
        <Spinner />
      </div>
    );

  return (
    <div className="cod-content-wrapper">
      <div className="cod-content">
        <div className="text-container">
          <Heading size="large">COD Settings</Heading>
          <Text type="subdued">
            Configure COD eligibility, rate, zones, product catalogues, fees{' '}
          </Text>
        </div>
        {!editMode ? <PreviewView handleEdit={() => setEditMode(true)} /> : <SettingsView />}
      </div>
      {editMode && <Button onClick={handleSave}>Save & apply</Button>}
    </div>
  );
}

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
  codEngineConfig: state.magicCODEngine,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      validateConfig,
      setEditMode,
      openModal,
    },
    dispatch,
  );
export default connect(mapStateToProps, mapDispatchToProps)(CODEngine);
