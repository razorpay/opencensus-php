import React, { useEffect, useMemo, useState } from 'react';
import { connect } from 'react-redux';

import SettingsLabel from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/SettingsLabel';
import ConfigItem from './ConfigItem';

import {
  COD_ENGINE_TYPES,
  POPOVER_CONTENT,
} from 'merchant/views/MagicCheckout/CODSettings/constants';
import { MAPPING_TYPES } from './constants';

const ConfigurationMapping = ({ isPreview = false, codEngine }): JSX.Element | null => {
  const { configs, item_categories, fee_rules, zones, validations } = codEngine;
  const [errorText, setErrorText] = useState('');

  const mappingType = useMemo(() => {
    if (configs.cod_engine_type === COD_ENGINE_TYPES.LOCATION) {
      return MAPPING_TYPES.ZONE;
    }
    return MAPPING_TYPES.CATEGORY;
  }, [configs.cod_engine_type]);

  useEffect(() => {
    if (!validations.mapping) {
      setErrorText('Required');
    } else {
      setErrorText('');
    }
  }, [validations, zones]);

  const hasZonesAndFeeRules = zones?.length > 0 && fee_rules?.length > 0;

  if (mappingType.label === MAPPING_TYPES.ZONE.label && !hasZonesAndFeeRules) return null;

  if (
    mappingType.label === MAPPING_TYPES.CATEGORY.label &&
    (!hasZonesAndFeeRules || item_categories.length === 0)
  )
    return null;

  return (
    <div className="cod-setting-item">
      {isPreview ? null : (
        <SettingsLabel
          value={`${mappingType.label} Configuration`}
          required
          errorText={errorText}
          popoverContent={
            configs.cod_engine_type === COD_ENGINE_TYPES.LOCATION
              ? POPOVER_CONTENT.zone_mapping
              : POPOVER_CONTENT.category_mapping
          }
        />
      )}
      <div className="cod-options-container">
        {codEngine?.[mappingType.storeKey].map((item) => (
          <ConfigItem type={mappingType} key={item.id} item={item} isPreview={isPreview} />
        ))}
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  codEngine: state.magicCODEngine,
});
export default connect(mapStateToProps, null)(ConfigurationMapping);
