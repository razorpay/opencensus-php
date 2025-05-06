import React from 'react';
import { ArrowSquareDownIcon, ArrowSquareUpIcon, Text, Box } from '@razorpay/blade/components';

interface PercentageChangeDisplayProps {
  percentageChange?: number;
  timeDate: string;
}
const PercentageChangeDisplay: React.FC<PercentageChangeDisplayProps> = ({
  percentageChange,
  timeDate,
}) => {
  // Handle undefined percentageChange
  if (!percentageChange || percentageChange == 0) return null;

  // Determine icon and color based on percentage change
  const isNegative = percentageChange < 0;
  const IconComponent = isNegative ? ArrowSquareDownIcon : ArrowSquareUpIcon;
  const textColor = isNegative
    ? 'feedback.text.negative.intense'
    : 'feedback.text.positive.intense';

  return (
    <Box display="flex" justifyContent="center" alignItems="center" gap="spacing.4">
      <Box display="flex" justifyContent="center" alignItems="center" gap="spacing.2">
        <IconComponent
          size="xlarge"
          color={isNegative ? 'feedback.icon.negative.intense' : 'feedback.icon.positive.intense'}
        />
        <Text size="small" variant="body" weight="semibold" color={textColor}>
          {percentageChange}% vs {timeDate}
        </Text>
      </Box>
    </Box>
  );
};

export default PercentageChangeDisplay;
