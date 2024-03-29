import React from 'react';
import { Box, BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';

import { MAIN_BANNER_TILES } from 'merchant/views/POS/constants';

import MainBannerTile from './MainBannerTiles';

const MainBannerTilesGroup = (): JSX.Element => {
  return (
    <BladeProvider themeTokens={bladeTheme} colorScheme="light">
      <Box
        height="100%"
        width="100%"
        display={{ base: 'none', xl: 'flex' }}
        flexDirection="column"
        justifyContent="flex-end"
        alignItems="center"
        paddingRight="spacing.5"
        paddingBottom="spacing.3"
        testID="main-banner-tiles"
      >
        {MAIN_BANNER_TILES.map(({ decription, image, styleProps }) => (
          <MainBannerTile
            key={decription}
            text={decription}
            image={image}
            name={decription}
            styleProps={styleProps}
          />
        ))}
      </Box>
    </BladeProvider>
  );
};

export default MainBannerTilesGroup;
