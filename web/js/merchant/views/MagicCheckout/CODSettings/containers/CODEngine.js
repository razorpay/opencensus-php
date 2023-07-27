import React from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';

import { Button, Heading, Text } from '@razorpay/blade/components';
import Spinner from 'common/ui/Spinner';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';

import EmptyView from 'merchant/views/MagicCheckout/CODSettings/EmptyView';
import PreviewView from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/PreviewView';
import SettingsView from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/SettingsView';
import ConfirmSettings from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/ConfirmSettings';

import { openModal } from 'merchant_common/reducers/modals';
import { validateConfig, setEditMode } from 'merchant/reducers/magicCheckout/codEngine/action';

import { COD_ENGINE_TYPES } from 'merchant/views/MagicCheckout/CODSettings/constants';
import { SERVICEABILITY_TYPES } from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Configuration/constants';

function CODEngine(props) {
  const { codEngineConfig, openModal, validateConfig, setEditMode } = props;
  const { configs, editMode, fee_rules, zones, item_categories } = codEngineConfig;

  const handleSave = () => {
    let hasError = false;
    if (zones.length === 0) {
      validateConfig('zones', false);
      hasError = true;
    }
    if (fee_rules.length === 0) {
      validateConfig('fee_rules', false);
      hasError = true;
    }
    if (configs.cod_engine_type === COD_ENGINE_TYPES.PRODUCT) {
      if (!item_categories.length) {
        validateConfig('item_categories', false);
        hasError = true;
      } else {
        const hasZones = item_categories.every(
          (c) => c.zones?.length || c.type === SERVICEABILITY_TYPES.BLACKLISTED,
        );
        if (!hasZones) {
          validateConfig('mapping', false);
          hasError = true;
        }
      }
    } else if (configs.cod_engine_type === COD_ENGINE_TYPES.LOCATION) {
      const hasFeeRules = zones.every((z) => z.fee_rules?.length);
      if (!hasFeeRules) {
        validateConfig('mapping', false);
        hasError = true;
      }
    }

    if (hasError) return;

    openModal({
      size: 'small',
      className: `magicToggleConfirmationModal`,
      component: <ConfirmSettings />,
    });
  };

  if (codEngineConfig.loading.summary)
    return (
      <div className="page-spinner-container">
        <Spinner />
      </div>
    );

  if (!configs.cod_engine) return <EmptyView viewName="COD settings" />;

  return (
    <ErrorBoundary team={Teams?.MAGIC_CHECKOUT} rank={Ranks.P0} resetOnProps>
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
    </ErrorBoundary>
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
