import React from 'react';
import { Box, Text } from '@razorpay/blade/components';

import CardImage from 'assets/pos/icons/card.svg';
import CartImage from 'assets/pos/icons/cart.svg';
import TruckImage from 'assets/pos/icons/truck.svg';
import { useBladeBreakpoints } from 'apps/pos/src/app/views/SelfServe/hooks';

import { DashedLines, TimelineIconStyled } from './styles';

type TIMELINE_ITEMS = {
  title: string;
  icon: string;
};

const TIMELINE_ITMES: TIMELINE_ITEMS[] = [
  {
    title: 'Select your favourite device.',
    icon: CardImage,
  },
  {
    title: 'Place an online order.',
    icon: CartImage,
  },
  {
    title: 'Sit tight while we bring your device to you in 2-3 days post KYC approval.',
    icon: TruckImage,
  },
];

const Timeline = (): JSX.Element => {
  const { isMobile } = useBladeBreakpoints();
  return (
    <Box display="flex" justifyContent={{ base: 'center', l: 'left' }} testID="banner-timeline">
      {TIMELINE_ITMES.map(({ title, icon }, index) => (
        <Box
          key={title}
          display="flex"
          flexDirection="column"
          alignItems="center"
          width="100%"
          maxWidth="200px"
          marginRight="spacing.1"
        >
          <Box display="flex" alignItems="center" width="100%">
            <Box width="100%">
              <DashedLines isHidden={index === 0} />
            </Box>
            <TimelineIconStyled src={icon} />
            <Box width="100%">
              <DashedLines isHidden={index === TIMELINE_ITMES.length - 1} />
            </Box>
          </Box>
          <Box width={{ base: '90%', l: 'fit-content' }}>
            <Text color="surface.text.gray.muted" textAlign={isMobile ? 'center' : 'left'}>
              {title}
            </Text>
          </Box>
        </Box>
      ))}
    </Box>
  );
};

export default Timeline;
