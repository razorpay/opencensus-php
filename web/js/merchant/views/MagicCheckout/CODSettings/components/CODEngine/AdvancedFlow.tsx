import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import CategorySettings from './CategorySettings';
import Configuration from './Configuration';

import { updateEngineConfig } from 'merchant/reducers/magicCheckout/codEngine/action';

import { COD_ENGINE_TYPES } from 'merchant/views/MagicCheckout/CODSettings/constants';

const AdvancedFlow = ({ item_categories, updateEngineConfig, configs }) => {
  useEffect(() => {
    if (configs.cod_engine_type === COD_ENGINE_TYPES.PRODUCT && item_categories.length === 0) {
      updateEngineConfig({
        cod_engine_type: COD_ENGINE_TYPES.LOCATION,
      });
    }
  }, [item_categories]);
  return (
    <>
      <CategorySettings />
      <Configuration />
    </>
  );
};
const mapStateToProps = (state) => ({
  item_categories: state.magicCODEngine.item_categories,
  configs: state.magicCODEngine.configs,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateEngineConfig,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(AdvancedFlow);
