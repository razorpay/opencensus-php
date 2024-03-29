import React from 'react';
import { Amount, AmountProps, Box, Text } from '@razorpay/blade/components';

import { useMobile } from 'common/hooks/useMobile';
import { Change } from 'merchant/widgets/common/Change';

import { TabCardProps } from './types';

const TabCard: React.FC<TabCardProps> = ({ isActive, tabData, cardPosition }) => {
  const isMobile = useMobile();
  const title = tabData.title ?? '';
  const change = tabData.data.change ?? 0;
  const changeType = tabData.data.change_type;
  const value = parseInt(`${tabData.data.value}`, 10) ?? 0;
  const currency = tabData.data.currency ?? 'INR';
  const trendText = `${change}%`;
  const trendSubText = tabData.data.sub_text ?? '';
  const isInverseMetric = Boolean(tabData.data.change_behavior_inverted);
  return (
    <Box
      display="flex"
      flexDirection="column"
      flex={1}
      height="100%"
      padding={['spacing.5', 'spacing.7']}
      backgroundColor={
        isActive ? 'surface.background.gray.intense' : 'surface.background.gray.moderate'
      }
      borderTopWidth="none"
      borderColor="surface.border.gray.muted"
      borderLeftWidth={isActive ? 'none' : 'thinner'}
      borderRightWidth={isActive ? 'none' : 'thinner'}
      borderBottomWidth="thicker"
      borderBottomColor={
        isActive ? 'surface.border.primary.normal' : 'surface.border.primary.muted'
      }
      gap="spacing.2"
      minHeight={{ base: '120px', m: 'initial' }}
      justifyContent="center"
    >
      <Text
        size="medium"
        color={isActive ? 'surface.text.gray.normal' : 'surface.text.gray.subtle'}
        weight="semibold"
      >
        {title}
      </Text>
      <Box display="flex" flexDirection="row" alignItems="center" gap="spacing.3">
        {value > 0 || cardPosition === 0 ? (
          <Amount
            value={value}
            currency={currency as AmountProps['currency']}
            type="heading"
            size="large"
          />
        ) : (
          <Text weight="semibold">--</Text>
        )}
        {value > 0 ? (
          <Change
            text={trendText}
            variant={change > 0 ? 'increase' : 'decrease'}
            type={changeType}
            isInverted={isInverseMetric}
          />
        ) : null}
      </Box>
      {!isMobile && value > 0 ? (
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
