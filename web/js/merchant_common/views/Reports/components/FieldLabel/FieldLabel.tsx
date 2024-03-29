import React from 'react';
import { FieldLabelWrapper } from 'merchant_common/views/Reports/components/styled';
import { Box, Text, NecessityIndicator } from 'merchant_common/views/Reports/components';

export const FieldLabel = ({ label, necessityIndicator }) => {
  return Boolean(label) ? (
    <FieldLabelWrapper>
      <Box display="flex" alignItems="center">
        <Text variant="body" size="small" weight="semibold" color="surface.text.gray.muted">
          {label}
        </Text>
        <NecessityIndicator necessityIndicator={necessityIndicator} />
      </Box>
    </FieldLabelWrapper>
  ) : null;
};
