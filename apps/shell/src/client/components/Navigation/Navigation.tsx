import React, { useState, Suspense, lazy } from 'react';
import { Box, useTheme, BladeProvider } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { bladeTheme } from '@razorpay/blade/tokens';
import { getItemFromLocalStorage } from '@libs/shared-utils';
import { useHideTopNavigation } from './hooks';
import { useStore } from '@federated/apps/shell/commonStore';

const TopNavigation = lazy(() => import('./TopNavigation'));
const FTUXBanner = lazy(() => import('./TopNavigation/FTUXBanner'));

export const ConnectedNavigationContainer = ({ children }): JSX.Element => {
  const { theme, colorScheme } = useTheme();
  const isFullPage = useHideTopNavigation();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isMobile = matchedDeviceType === 'mobile';
  const userId = useStore((state) => state?.session?.user?.user?.id || '');

  const [showFtuxBanner, setShowFtuxBanner] = useState(
    getItemFromLocalStorage(`showOnenavFtuxBanner_${userId}`) !== 'false',
  );

  return (
    <Box
      backgroundColor={colorScheme === 'light' ? '#E3EAF3' : '#273244'}
      height="100vh"
      display="flex"
      flexDirection="column"
    >
      <Suspense fallback={null}>
        <TopNavigation />
      </Suspense>
      <Box
        marginX={{ base: 'spacing.0', m: isFullPage ? 'spacing.0' : 'spacing.3' }}
        overflow="hidden"
        backgroundColor="surface.background.gray.moderate"
        borderTopLeftRadius={isMobile ? 'none' : 'medium'}
        display="flex"
        flexDirection="column"
        flexGrow="1"
      >
        <Box
          overflow="hidden"
          position="relative"
          height="100%"
          display="flex"
          flexDirection="column"
        >
          {showFtuxBanner && !isMobile && (
            <Suspense fallback={null}>
              <BladeProvider themeTokens={bladeTheme} colorScheme="dark">
                <FTUXBanner handleClose={() => setShowFtuxBanner(false)} />
              </BladeProvider>
            </Suspense>
          )}
          {children}
        </Box>
      </Box>
    </Box>
  );
};

export default ConnectedNavigationContainer;
