import React from 'react';
import { BladeProvider, Button, MenuIcon, SideNav } from '@razorpay/blade/components';
import { bladeTheme, createTheme } from '@razorpay/blade/tokens';
import { Helmet } from 'react-helmet-async';
import { ActivationBanner, BoxSizingReset, getSubWidget } from './utils';
import { IOneNavigationResponse } from './types';

const Sidebar = ({ navigation }: { navigation: IOneNavigationResponse }) => {
  const [isMobileOpen, setIsMobileOpen] = React.useState(false);

  const brandColor = navigation.one_nav_config.brand_colour;
  const sideBar = navigation.components[0].components[0];
  const activationBannerProgress = sideBar.one_nav_config?.banner?.activation_progress ?? 0;
  const isNcEligible = sideBar.one_nav_config?.banner?.nc_eligible;

  const getThemeToken = () => (brandColor ? createTheme({ brandColor }) : bladeTheme);

  return (
    <BladeProvider themeTokens={getThemeToken()} colorScheme="light">
      <BoxSizingReset>
        <SideNav
          position="absolute"
          banner={
            activationBannerProgress >= 0 ? (
              <ActivationBanner
                activationProgress={activationBannerProgress}
                isNCEligible={isNcEligible}
              />
            ) : undefined
          }
          isOpen={isMobileOpen}
          onDismiss={() => setIsMobileOpen(false)}
        >
          {sideBar.components.map((navigationItem) => getSubWidget(navigationItem))}
        </SideNav>
      </BoxSizingReset>
      {/* Trigger for mobile sidebar */}
      <Button
        display={{ base: undefined, m: 'none' }}
        icon={MenuIcon}
        variant="secondary"
        onClick={() => setIsMobileOpen(true)}
        position="fixed"
        top="spacing.4"
        left="spacing.4"
        zIndex="2"
      />
      {isMobileOpen ? (
        <Helmet
          style={[
            {
              cssText: `
                * {
                    box-sizing: border-box;
                }
            `,
            },
          ]}
        />
      ) : null}
    </BladeProvider>
  );
};

export { Sidebar as SidebarNavigation };
