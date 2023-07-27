import React, { useState } from 'react';
import { connect } from 'react-redux';
import { ActionItem } from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Configuration/styled';
import { Box, Text } from '@razorpay/blade/components';
import SettingsToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsToggle';
import ZoneSelector from './ZoneSelector';

const CategoryMapping = ({
  selectedRuleIds,
  setSelectedRuleIds,
  cod_engine_config,
  isCODBlocked,
  setIsCODBlocked,
}): JSX.Element => {
  const { zones } = cod_engine_config;
  const [openId, setOpenId] = useState(zones[0]?.id);

  const handleToggleClick = (toggleState) => {
    setIsCODBlocked(toggleState);
  };

  return (
    <div>
      <ActionItem>
        <Box marginTop="spacing.4" width="110px">
          <Text>Block COD</Text>
        </Box>
        <Box flex="1" display="flex" flexDirection="column" padding="12px 20px">
          <SettingsToggle setting={{ value: isCODBlocked }} onToggle={handleToggleClick} />
        </Box>
      </ActionItem>
      <ActionItem borderTop="none">
        <Box marginTop="spacing.4" width="110px">
          <Text>Select Zones {!isCODBlocked ? <span className="required">*</span> : null}</Text>
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
                isCODBlocked={isCODBlocked}
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
