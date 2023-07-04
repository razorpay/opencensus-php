import React from 'react';

import { Box, Text } from '@razorpay/blade/components';

import { ActionItem } from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Configuration/styled';

import FeeRuleSelector from './FeeRuleSelector';

const ZoneMapping = ({
  selectedRuleIds,
  setSelectedRuleIds,
}: {
  selectedRuleIds: string[];
  setSelectedRuleIds: (id: string) => void;
}) => {
  return (
    <div>
      <ActionItem>
        <Box marginTop="spacing.4" width="110px">
          <Text>
            Select Slabs <span className="required">*</span>
          </Text>
        </Box>
        <FeeRuleSelector
          selectedRuleIds={selectedRuleIds}
          setSelectedRuleIds={setSelectedRuleIds}
        />
      </ActionItem>
    </div>
  );
};

export default ZoneMapping;
