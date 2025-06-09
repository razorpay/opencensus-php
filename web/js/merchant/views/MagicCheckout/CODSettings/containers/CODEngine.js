import React from 'react';
import { Button, Text, Heading } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import Spinner from 'common/ui/Spinner';
import { setEditMode, validateConfig } from 'merchant/reducers/magicCheckout/codEngine/action';
import EmptyView from 'merchant/views/MagicCheckout/CODSettings/EmptyView';
import { SERVICEABILITY_TYPES } from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Configuration/constants';
import PreviewView from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/PreviewView';
import SettingsView from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/SettingsView';
import ConfirmSettings from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/ConfirmSettings';
import { COD_ENGINE_TYPES } from 'merchant/views/MagicCheckout/CODSettings/constants';
import { openModal } from 'merchant_common/reducers/modals';
import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';

function CODEngine(props) {
  const { codEngineConfig, openModal, validateConfig, setEditMode, settings } = props;
  const { configs, editMode, fee_rules, zones, item_categories } = codEngineConfig;
  const { rcodEnabled, platform } = settings;

  const handleSave = () => {
    let hasError = false;
    if (!zones.length && !rcodEnabled) {
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

  if ((rcodEnabled && !configs.rcod) || (!rcodEnabled && !configs.cod_engine)) {
    return <EmptyView viewName="COD settings" />;
  }

  return (
    <ErrorBoundary team={Teams?.MAGIC_CHECKOUT} rank={Ranks.P0} resetOnProps>
      <div className="cod-content-wrapper">
        <div className="cod-content">
          <div className="text-container">
            <Heading size="medium">COD Settings</Heading>
            <Text color="surface.text.gray.muted">
              {`Configure COD eligibility, rate, ${
                !rcodEnabled ? 'zones, product catalogues, ' : ''
              }fees`}
            </Text>
          </div>
          {!editMode ? <PreviewView handleEdit={() => setEditMode(true)} /> : <SettingsView />}
        </div>
        {editMode && platform !== PLATFORMS.VALUES.MAGENTO && (
          <Button onClick={handleSave}>Save & apply</Button>
        )}
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
