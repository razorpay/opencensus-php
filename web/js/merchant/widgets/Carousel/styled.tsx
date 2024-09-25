import React, { ReactNode } from 'react';
import { Box, BoxProps } from '@razorpay/blade/components';
import styled from 'styled-components';

import { getBackgroundImage } from 'merchant/widgets/utils';

const DropShadowBox = styled.div`
  & > div {
    filter: drop-shadow(0px 2px 16px #1326441a);
  }
`;

interface CarouselWidgetWrapperProps {
  children: ReactNode;
  width?: BoxProps['width'];
  backgroundImage?: string;
  hide?: boolean;
}

export const CarouselWidgetWrapper = ({
  children,
  width = { base: '100%', m: 'calc(100% - 40px)' },
  backgroundImage,
  hide = false,
}: CarouselWidgetWrapperProps) => {
  return (
    <Box
      borderRadius={!hide ? 'medium' : undefined}
      marginX={{ base: 'spacing.0', m: 'spacing.6' }}
      paddingY={!hide ? 'spacing.6' : undefined}
      paddingX={!hide ? 'spacing.5' : undefined}
      backgroundImage={backgroundImage ? getBackgroundImage(backgroundImage) : undefined}
      backgroundColor={hide ? 'transparent' : 'surface.background.gray.intense'}
      backgroundSize="cover"
      backgroundPosition="center center"
      testID="carousel-widget-wrapper"
      elevation={!hide ? 'lowRaised' : undefined}
      width={width}
    >
      <DropShadowBox>{children}</DropShadowBox>
    </Box>
  );
};
