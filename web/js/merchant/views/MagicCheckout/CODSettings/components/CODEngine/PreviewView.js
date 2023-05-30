import React from 'react';
import { Heading, Link, Text, EditComposeIcon } from '@razorpay/blade/components';
import DataTable from 'common/ui/Table/DataTable';
import {
  slabRange,
  slatRate,
  zoneCountry,
  zoneName,
  zoneStates,
} from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/cellItem';
import { connect } from 'react-redux';
const PreviewItem = ({ label, children }) => (
  <div className="preview-item">
    <Text size="small" type="subdued">
      {label}
    </Text>
    {children}
  </div>
);
const PreviewView = ({ cod_engine_config, handleEdit }) => {
  const TABLE_COLUMNS = [slabRange];
  if (cod_engine_config.configs.rate_slabs) {
    TABLE_COLUMNS.push(slatRate);
  }
  return (
    <div className="preview-view">
      <div className="preview-item">
        <div className="heading">
          <Heading>Settings</Heading>
          <Link onClick={handleEdit} icon={EditComposeIcon} iconPosition="left" variant="button">
            Edit
          </Link>
        </div>
      </div>
      <PreviewItem label="Type of setting">
        <Text weight="bold">{cod_engine_config.configs.engine}</Text>
      </PreviewItem>
      <PreviewItem label="COD eligibility slabs & fee">
        <p className="rate-text">
          {cod_engine_config.configs.rate_slabs ? 'Yes add COD fee' : 'No, don’t add COD fee'}
        </p>
        {cod_engine_config.fee_rules ? (
          <DataTable
            customClass={`settings-table ${TABLE_COLUMNS.length === 1 ? 'single-column' : ''}`}
            items={cod_engine_config.fee_rules}
            columns={TABLE_COLUMNS}
          />
        ) : (
          <Text weight="bold">Disabled</Text>
        )}
      </PreviewItem>

      <PreviewItem label="COD eligibility zones">
        {cod_engine_config.zones ? (
          <DataTable
            customClass="settings-table"
            items={cod_engine_config.zones}
            columns={[zoneName, zoneCountry, zoneStates]}
          />
        ) : (
          <Text weight="bold">Enabled for all shopify shipping zones</Text>
        )}
      </PreviewItem>
    </div>
  );
};

const mapStateToProps = (state) => ({
  cod_engine_config: state.magicCODEngine,
});

export default connect(mapStateToProps, null)(PreviewView);
