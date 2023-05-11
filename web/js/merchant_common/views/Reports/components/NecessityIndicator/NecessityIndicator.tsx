import React from 'react';
import { NecessityIndicatorType } from './types';
import { Box, Text } from 'merchant_common/views/Reports/components';

export const NecessityIndicator = ({
  necessityIndicator,
}: {
  necessityIndicator?: NecessityIndicatorType;
}): JSX.Element => {
  const renderIndicator = () => {
    switch (necessityIndicator) {
      case 'optional':
        return (
          <Box height="spacing.5">
            <Text type="muted" variant="caption" color="surface.text.placeholder.lowContrast">
              &nbsp;&nbsp;(optional)
            </Text>
          </Box>
        );
      case 'required':
        return (
          <Box height="spacing.6">
            <Text type="muted" variant="body" color="feedback.text.negative.lowContrast">
              *
            </Text>
          </Box>
        );
      default:
        return <></>;
    }
  };
  return necessityIndicator ? renderIndicator() : <></>;
};
