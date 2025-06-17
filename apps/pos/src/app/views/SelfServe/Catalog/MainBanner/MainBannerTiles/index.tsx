import React, { useState } from 'react';
import { BladeProvider, Text } from '@razorpay/blade/components';

import {
  MainBannerTileFooter,
  MainBannerTileImage,
  MainBannerTileOverlay,
  MainBannerTileText,
  MainBannerTitleContainer,
} from 'apps/pos/src/app/views/SelfServe/Catalog/MainBanner/styles';
import { MainBannerItemStyleProps } from 'apps/pos/src/app/views/SelfServe/types';
import { bladeTheme } from '@razorpay/blade/tokens';

type MainBannerTile = {
  name: string;
  text: string;
  image: string;
  styleProps: MainBannerItemStyleProps;
};

const MainBannerTile = ({ name, text, image, styleProps }: MainBannerTile): JSX.Element => {
  const [isHovered, setIsHovered] = useState<boolean>(false);
  const handleMouseEnter = () => setIsHovered(true);
  const handleMouseLeave = () => setIsHovered(false);

  return (
    <BladeProvider themeTokens={bladeTheme} colorScheme="dark">
      <MainBannerTitleContainer onMouseEnter={handleMouseEnter} onMouseLeave={handleMouseLeave}>
        <MainBannerTileOverlay isHovered={isHovered} />
        <MainBannerTileFooter>
          <MainBannerTileText isHovered={isHovered}>
            <Text size="small" color="surface.text.gray.muted" textAlign="center">
              {text}
            </Text>
          </MainBannerTileText>
        </MainBannerTileFooter>
        <MainBannerTileImage isHovered={isHovered} imageStyles={styleProps}>
          <img src={image} alt={name} />
        </MainBannerTileImage>
      </MainBannerTitleContainer>
    </BladeProvider>
  );
};

export default MainBannerTile;
