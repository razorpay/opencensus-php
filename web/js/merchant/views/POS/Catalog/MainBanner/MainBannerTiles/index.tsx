import React, { useState } from 'react';
import { Text } from '@razorpay/blade/components';

import {
  MainBannerTileFooter,
  MainBannerTileImage,
  MainBannerTileOverlay,
  MainBannerTileText,
  MainBannerTitleContainer,
} from 'merchant/views/POS/Catalog/MainBanner/styles';
import { MainBannerItemStyleProps } from 'merchant/views/POS/types';

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
    <MainBannerTitleContainer onMouseEnter={handleMouseEnter} onMouseLeave={handleMouseLeave}>
      <MainBannerTileOverlay isHovered={isHovered} />
      <MainBannerTileFooter>
        <MainBannerTileText isHovered={isHovered}>
          <Text size="small" color="surface.text.muted.highContrast" textAlign="center">
            {text}
          </Text>
        </MainBannerTileText>
      </MainBannerTileFooter>
      <MainBannerTileImage isHovered={isHovered} imageStyles={styleProps}>
        <img src={image} alt={name} />
      </MainBannerTileImage>
    </MainBannerTitleContainer>
  );
};

export default MainBannerTile;
