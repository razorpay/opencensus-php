import React from 'react';
import { Box } from '@razorpay/blade/components';

import { Description } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/Meta/Description';
import { Name } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/Meta/Name';

import type { Rule } from 'merchant/reducers/magicCheckout/magicxACODRules/types';

type RuleMetaProps = {
  rule: Rule;
  handleChange: (prop: string, value: string) => void;
};
export const RuleMeta: React.FC<RuleMetaProps> = ({ rule, handleChange }) => {
  return (
    <Box display="flex" gap="spacing.7">
      <Name value={rule.name} onChange={(value: string) => handleChange('name', value)} />
      <Description
        value={rule.description}
        onChange={(value: string) => handleChange('description', value)}
      />
    </Box>
  );
};
