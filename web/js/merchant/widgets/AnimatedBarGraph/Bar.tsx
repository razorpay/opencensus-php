import React from 'react';
import { BarProps, IndicatorColorIcon, IndicatorColorText } from './types';
import { BarInner, BarOuter } from './styled';
import BarLabel from './BarLabel';
import { ArrowDownIcon, ArrowUpIcon, Box, Text } from '@razorpay/blade/components';

function Bar({
  variant,
  height,
  labelAlignment,
  showChangeIndicator,
  barLabel,
  barValue,
}: BarProps): JSX.Element {
  function getIndicatorColor(component: 'text'): IndicatorColorText;
  function getIndicatorColor(component: 'icon'): IndicatorColorIcon;
  function getIndicatorColor(component: 'text' | 'icon'): IndicatorColorText | IndicatorColorIcon {
    switch (variant) {
      case 'neutral':
        return `feedback.${component}.neutral.intense`;
      case 'positive':
        return `feedback.${component}.positive.intense`;
      case 'negative':
        return `feedback.${component}.negative.intense`;
      default:
        return `feedback.${component}.neutral.intense`;
    }
  }

  const getIndicatorIcon = () => {
    switch (height) {
      case 'high':
        return <ArrowUpIcon marginBottom="spacing.3" color={getIndicatorColor('icon')} />;
      case 'low':
        return <ArrowDownIcon marginBottom="spacing.3" color={getIndicatorColor('icon')} />;
      default:
        return null;
    }
  };

  return (
    <Box height="100%" display="flex" flexDirection="column" justifyContent="flex-end">
      {showChangeIndicator && (
        <Box display="flex" flexDirection="column" alignItems="center">
          {getIndicatorIcon()}
          {barValue && (
            <Text
              variant="body"
              size="medium"
              weight="semibold"
              marginBottom="spacing.3"
              color={getIndicatorColor('text')}
            >
              {barValue}
            </Text>
          )}
        </Box>
      )}
      <BarOuter variant={variant} height={height}>
        <BarLabel variant={variant} align={labelAlignment} label={barLabel} />
        <BarInner variant={variant} />
      </BarOuter>
    </Box>
  );
}

export default Bar;
