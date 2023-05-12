import React from 'react';
import { Text, InfoIcon, Box } from 'merchant_common/views/Reports/components';

export const FieldFooter = ({
  helpText,
  validation,
  errorText,
}: {
  helpText?: string;
  validation: boolean;
  errorText?: string;
}) => {
  return (validation ? Boolean(helpText) : Boolean(errorText)) ? (
    <Box marginTop="spacing.2" display="flex" alignItems="center">
      {!validation ? (
        <InfoIcon marginRight="spacing.1" color="feedback.icon.negative.lowContrast" size="small" />
      ) : null}
      <Text
        variant="caption"
        type="muted"
        weight="regular"
        color={validation ? 'surface.text.muted.lowContrast' : 'feedback.text.negative.lowContrast'}
      >
        {validation ? helpText : errorText}
      </Text>
    </Box>
  ) : null;
};
