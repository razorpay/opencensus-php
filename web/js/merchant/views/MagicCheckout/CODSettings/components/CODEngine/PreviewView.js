import React, { useMemo } from 'react';
import { Box, EditComposeIcon, Link, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';

import DataTable from 'common/ui/Table/DataTable';
import Configuration from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Configuration';
import { MAPPING_TYPES } from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Configuration/constants';
import {
  slabName,
  slabRange,
  slatRate,
  zoneCountry,
  zoneName,
  zoneStates,
} from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/cellItem';
import { COD_ENGINES } from 'merchant/views/MagicCheckout/CODSettings/constants';

const PreviewItem = ({ label, children }) => (
  <div className="preview-item">
    <Box marginBottom="spacing.3">
      <Text size="small" color="surface.text.gray.muted">
        {label}
      </Text>
    </Box>
    {children}
  </div>
);
const PreviewView = ({ cod_engine_config, handleEdit, isRCOD }) => {
  const { configs, fee_rules, zones, item_categories } = cod_engine_config;
  const { rate_slabs, engine } = configs;

  const isAdvanceView = engine === COD_ENGINES.ADVANCED && !isRCOD;
  const mappingType = useMemo(() => {
    if (isAdvanceView) {
      if (item_categories.length) return MAPPING_TYPES.CATEGORY.label;
      else if (zones.length) return MAPPING_TYPES.ZONE.label;
    }
    return '';
  }, [item_categories, zones]);

  const TABLE_COLUMNS = [];
  if (isRCOD) {
    TABLE_COLUMNS.push(slabName);
  }
  TABLE_COLUMNS.push(slabRange);
  if (cod_engine_config.configs.rate_slabs) {
    TABLE_COLUMNS.push(slatRate);
  }

  return (
    <div className="preview-view">
      <div className="preview-item">
        <div className="heading">
          <Text size="large">Settings</Text>
          <Link onClick={handleEdit} icon={EditComposeIcon} iconPosition="left" variant="button">
            Edit
          </Link>
        </div>
      </div>
      {!isRCOD ? (
        <PreviewItem label="Type of setting">
          <Text weight="semibold">{engine}</Text>
        </PreviewItem>
      ) : null}
      {isAdvanceView ? (
        <PreviewItem label={`${mappingType} configuration`}>
          <Configuration isPreview />
        </PreviewItem>
      ) : (
        <>
          <PreviewItem label="COD eligibility slabs & fee">
            <p className="rate-text">{rate_slabs ? 'Yes, add COD fee' : 'No, don’t add COD fee'}</p>
            {fee_rules ? (
              <DataTable
                customClass={`settings-table ${TABLE_COLUMNS.length === 1 ? 'single-column' : ''}`}
                items={cod_engine_config.fee_rules}
                columns={TABLE_COLUMNS}
              />
            ) : (
              <Text weight="semibold">Disabled</Text>
            )}
          </PreviewItem>

          {!isRCOD ? (
            <PreviewItem label="COD eligibility zones">
              {zones ? (
                <DataTable
                  customClass="settings-table"
                  items={cod_engine_config.zones}
                  columns={[zoneName, zoneCountry, zoneStates]}
                />
              ) : (
                <Text weight="semibold">Enabled for all shopify shipping zones</Text>
              )}
            </PreviewItem>
          ) : null}
        </>
      )}
    </div>
  );
};

const mapStateToProps = (state) => ({
  cod_engine_config: state.magicCODEngine,
  isRCOD: state.magic_settings.rcodEnabled,
});

export default connect(mapStateToProps, null)(PreviewView);
