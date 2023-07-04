import React, { useState } from 'react';
import { connect } from 'react-redux';
import { ActionItem } from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Configuration/styled';
import { Box, Text } from '@razorpay/blade/components';
import ZoneSelector from './ZoneSelector';

const CategoryMapping = ({
  selectedRuleIds,
  setSelectedRuleIds,
  cod_engine_config,
}): JSX.Element => {
  const { zones } = cod_engine_config;
  const [openId, setOpenId] = useState(zones[0]?.id);

  return (
    <div>
      <ActionItem>
        <Box marginTop="spacing.4" width="110px">
          <Text>
            Select Zones <span className="required">*</span>
          </Text>
        </Box>
        <Box flex="1" display="flex" flexDirection="column">
          {zones.map((zone) => {
            return (
              <ZoneSelector
                key={zone.id}
                zone={zone}
                selectedRuleIds={selectedRuleIds}
                setSelectedRuleIds={setSelectedRuleIds}
                openId={openId}
                setOpenId={setOpenId}
              />
            );
          })}
        </Box>
      </ActionItem>
    </div>
  );
};

const mapStateToProps = (state) => ({
  cod_engine_config: state.magicCODEngine,
});

export default connect(mapStateToProps, null)(CategoryMapping);
