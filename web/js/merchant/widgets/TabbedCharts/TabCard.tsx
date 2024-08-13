import React from 'react';
import { Amount, AmountProps, Box, Text } from '@razorpay/blade/components';
import { convertToMajorUnit } from '@razorpay/i18nify-js/currency';

import { useMobile } from 'common/hooks/useMobile';
import { Change } from 'merchant/widgets/common/Change';
import { TooltipWidget } from 'merchant/widgets/common/Tooltip';
import { convertToNumber } from 'merchant/widgets/common/utils';

import { TabCardProps } from './types';

const TabCard: React.FC<TabCardProps> = ({ isActive, tabData, cardPosition }) => {
  const isMobile = useMobile();
  const title = tabData.title ?? '';
  const change = convertToNumber(tabData.data.change);
  const changeType = tabData.data.change_type;
  const currency = tabData.data.currency || 'INR';
  const value = parseInt(`${tabData.data.value}`, 10) ?? 0;
  const formattedValue = convertToMajorUnit(value, { currency: currency as any });
  const trendText = `${change}%`;
  const trendSubText = tabData.data.sub_text ?? '';
  const isInverseMetric = Boolean(tabData.data.change_behavior_inverted);
  const tooltipText = tabData.tooltip_text ?? '';
  const hasValue = value > 0;

  return (
    <Box
      display="flex"
      flexDirection="column"
      flex={1}
      height="100%"
      padding={['spacing.5', 'spacing.7']}
      backgroundColor={
        isActive ? 'surface.background.gray.intense' : 'surface.background.gray.subtle'
      }
      borderTopWidth="none"
      borderColor="surface.border.gray.subtle"
      borderLeftWidth={isActive ? 'none' : 'thinner'}
      borderRightWidth={isActive ? 'none' : 'thinner'}
      borderBottomWidth="thicker"
      borderBottomColor={
        isActive ? 'surface.border.primary.normal' : 'surface.border.primary.muted'
      }
      gap="spacing.2"
      minHeight={{ base: '120px', m: 'initial' }}
      justifyContent="flex-start"
    >
      <Box display="flex" gap="spacing.2" alignItems="center">
        <Text
          size="medium"
          color={isActive ? 'surface.text.gray.normal' : 'surface.text.gray.subtle'}
          weight="semibold"
        >
          {title}
        </Text>
        {tooltipText && <TooltipWidget tooltip_text={tooltipText} />}
      </Box>
      <Box display="flex" flexDirection="row" alignItems="center" gap="spacing.3">
        {hasValue || cardPosition === 0 ? (
          <Amount
            value={formattedValue}
            currency={currency as AmountProps['currency']}
            type="heading"
            size="large"
            weight="semibold"
          />
        ) : (
          <Text weight="semibold">--</Text>
        )}
        {hasValue ? (
          <Change
            text={trendText}
            variant={change > 0 ? 'increase' : 'decrease'}
            type={changeType}
            isInverted={isInverseMetric}
          />
        ) : null}
      </Box>
      {!isMobile && hasValue ? (
        <Text
          size="medium"
          weight="semibold"
          color={isActive ? 'surface.text.gray.normal' : 'surface.text.gray.muted'}
        >
          {trendSubText}
        </Text>
      ) : null}
    </Box>
  );
};

export default TabCard;
