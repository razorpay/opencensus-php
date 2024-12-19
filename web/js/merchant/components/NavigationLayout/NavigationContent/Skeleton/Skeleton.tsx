import React from 'react';
import styled from 'styled-components';
import { Link } from 'react-router-dom';
import {
  Box,
  Text,
  Heading,
  TopNav,
  TopNavBrand,
  TopNavContent,
  TopNavActions,
  TabNav,
  TabNavItem,
  TabNavItems,
  Menu,
  MenuItem,
  MenuOverlay,
  MenuHeader,
  MenuFooter,
  Badge,
  useTheme,
  HomeIcon,
  Link as BladeLink,
  SearchInput,
  Avatar,
  ChevronDownIcon,
  ChevronRightIcon,
  RazorpayxPayrollIcon,
  AcceptPaymentsIcon,
  AwardIcon,
  SIDE_NAV_EXPANDED_L1_WIDTH_BASE,
  SIDE_NAV_EXPANDED_L1_WIDTH_XL,
  SideNav,
  SideNavBody,
  Skeleton as BladeSkeleton,
  SideNavFooter,
  RazorpayXIcon,
  UsersIcon,
  Spinner,
  Divider,
} from '@razorpay/blade/components';
import { makeSize } from '@razorpay/blade/utils';
import type { TabNavItemProps, IconComponent, SideNavProps } from '@razorpay/blade/components';
import RazorpayLogo from '../../TopNavigation/RazorpayLogo';

const NavLinkShimmer = (): React.ReactElement => {
  return (
    <Box display="flex" flexWrap="wrap" gap="spacing.2" padding="spacing.2">
      <BladeSkeleton borderRadius="medium" height="32px" width="100%" />
    </Box>
  );
};

const NavSectionShimmer = (): React.ReactElement => {
  return (
    <Box display="flex" flexWrap="wrap" gap="spacing.2" padding="spacing.2" marginTop={'spacing.5'}>
      <BladeSkeleton borderRadius="medium" height="20px" width="50%" />
    </Box>
  );
};

const SideNavWithShimmer = ({
  isOpen,
  onDismiss,
}: Pick<SideNavProps, 'isOpen' | 'onDismiss'>): React.ReactElement => {
  return (
    <SideNav isOpen={isOpen} onDismiss={onDismiss} position="absolute">
      <SideNavBody>
        <NavLinkShimmer />
        <NavLinkShimmer />
        <NavLinkShimmer />
        <NavLinkShimmer />

        <NavSectionShimmer />

        <NavLinkShimmer />
        <NavLinkShimmer />
        <NavLinkShimmer />
        <NavLinkShimmer />
      </SideNavBody>
      <SideNavFooter>
        <NavLinkShimmer />
        <NavLinkShimmer />
      </SideNavFooter>
    </SideNav>
  );
};

const TabNavItemLink = React.forwardRef<
  HTMLAnchorElement,
  Omit<TabNavItemProps, 'as'> & {
    activeOnLinks?: string[];
  }
>((props, ref) => {
  return (
    <TabNavItem
      ref={ref}
      onClick={(e) => {
        e.preventDefault();
      }}
      {...props}
      as={Link}
    />
  );
});

const ExploreItem = ({
  icon: Icon,
  title,
  description,
}: {
  icon: IconComponent;
  title?: string;
  description: string;
}): React.ReactElement => {
  return (
    <Box display="flex" gap="spacing.4">
      <Box
        borderRadius="medium"
        padding="spacing.5"
        backgroundColor="surface.background.gray.subtle"
      >
        <Icon color="interactive.icon.neutral.subtle" size="medium" />
      </Box>
      <Box>
        <Text color="surface.text.gray.subtle" size="medium" weight="semibold">
          {title}
        </Text>
        <Text size="small" color="surface.text.gray.muted">
          {description}
        </Text>
      </Box>
    </Box>
  );
};

const DashboardBackground = styled.div(() => {
  return {
    height: '100vh',
    //  background: 'radial-gradient(94.74% 64.44% at 29.03% 15.17%, #FFFFFF 0%, #90A5BB 100%)',
  };
});

const Skeleton = (): React.ReactElement => {
  const { platform } = useTheme();
  const isMobile = platform === 'onMobile';

  return (
    <DashboardBackground>
      <Box backgroundColor="surface.background.gray.subtle">
        {isMobile ? (
          <>
            <TopNav backgroundColor="surface.background.gray.intense">
              <BladeLink icon={HomeIcon} size="medium" onClick={(e) => e.preventDefault()}>
                Home
              </BladeLink>
              <Heading textAlign="center" size="small" weight="semibold">
                Payments
              </Heading>
              <Avatar size="medium" name="Razorpay Merchant" />
            </TopNav>
            <Box paddingX={'spacing.3'} backgroundColor="surface.background.gray.intense">
              <Divider />
            </Box>
          </>
        ) : (
          <TopNav>
            <TopNavBrand>
              <RazorpayLogo />
            </TopNavBrand>
            <TopNavContent>
              <TabNav
                items={[
                  {
                    href: '/payments',
                    title: 'Payments',
                    icon: AcceptPaymentsIcon,
                    description: 'Manage payments effortlessly.',
                  },
                  {
                    href: '/banking',
                    title: 'Banking',
                    icon: RazorpayXIcon,
                    description: 'Automate payroll with ease.',
                  },
                  {
                    href: '/payroll',
                    title: 'Payroll',
                    icon: RazorpayxPayrollIcon,
                    description: 'Automate payroll with ease.',
                  },
                  {
                    href: '/partners',
                    title: 'Partners',
                    icon: UsersIcon,
                    description: 'Fast, one-click checkout.',
                  },
                  {
                    href: '/rize',
                    title: 'Rize',
                    icon: AwardIcon,
                    description: 'Boost your business growth.',
                  },
                ]}
              >
                {({ items, overflowingItems }) => {
                  return (
                    <>
                      <TabNavItems>
                        {items.map((item) => {
                          return (
                            <TabNavItemLink
                              key={item.title}
                              title={item.title}
                              href={item.href}
                              icon={item.icon}
                            />
                          );
                        })}
                      </TabNavItems>
                      {overflowingItems.length ? (
                        <Menu openInteraction="hover">
                          <TabNavItem
                            title={'More'}
                            trailing={<ChevronDownIcon />}
                            isActive={false}
                          />
                          <MenuOverlay>
                            <MenuHeader
                              title="Products for you"
                              trailing={
                                <Badge emphasis="subtle" color="notice">
                                  Recommended
                                </Badge>
                              }
                            />
                            {overflowingItems.map((item) => {
                              return (
                                <MenuItem
                                  key={item.href}
                                  onClick={(e) => {
                                    e.preventDefault();
                                  }}
                                >
                                  <ExploreItem
                                    icon={item.icon!}
                                    title={item.title}
                                    description={item.description!}
                                  />
                                </MenuItem>
                              );
                            })}
                            <MenuFooter>
                              <BladeLink href="" icon={ChevronRightIcon} iconPosition="right">
                                View all products
                              </BladeLink>
                            </MenuFooter>
                          </MenuOverlay>
                        </Menu>
                      ) : null}
                    </>
                  );
                }}
              </TabNav>
            </TopNavContent>
            <TopNavActions>
              <SearchInput
                placeholder="Search in payments"
                accessibilityLabel="Search Across Razorpay"
              />
              <Menu openInteraction="click">
                <Avatar size="medium" name="Razorpay Merchant" />
                <MenuOverlay>
                  <Box display="flex" gap="spacing.1" padding="spacing.2" alignItems="center">
                    <Box
                      display="flex"
                      flexWrap="wrap"
                      gap="spacing.1"
                      padding="spacing.2"
                      width={'120px'}
                    >
                      <BladeSkeleton borderRadius="medium" height="15px" width="100%" />
                    </Box>
                  </Box>
                  <Box display="flex" gap="spacing.1" padding="spacing.2" alignItems="center">
                    <Box
                      display="flex"
                      flexWrap="wrap"
                      gap="spacing.1"
                      padding="spacing.2"
                      width={'120px'}
                    >
                      <BladeSkeleton borderRadius="medium" height="15px" width="100%" />
                    </Box>
                  </Box>
                </MenuOverlay>
              </Menu>
            </TopNavActions>
          </TopNav>
        )}
        <Box
          overflow="hidden"
          position="relative"
          borderRadius="large"
          borderTopRightRadius="none"
          borderBottomLeftRadius="none"
          borderBottomRightRadius="none"
          height="100%"
          marginX={{ base: 'spacing.0', m: 'spacing.3' }}
        >
          {isMobile ? null : <SideNavWithShimmer />}
          <Box
            marginLeft={{
              base: '0px',
              m: makeSize(SIDE_NAV_EXPANDED_L1_WIDTH_BASE),
              xl: makeSize(SIDE_NAV_EXPANDED_L1_WIDTH_XL),
            }}
            height="calc(100vh - 58px)"
          >
            <Box
              height="100vh"
              padding="spacing.5"
              overflowY="scroll"
              backgroundColor="surface.background.gray.intense"
              justifyContent="center"
              alignItems="center"
              display="flex"
            >
              <Box
                width={{ base: 'max-content', m: '100%' }}
                height="100vh"
                display="flex"
                justifyContent="center"
                alignItems="center"
              >
                <Spinner accessibilityLabel="content-spinner" size="xlarge" />
              </Box>
            </Box>
          </Box>
        </Box>
      </Box>
    </DashboardBackground>
  );
};

export { Skeleton };
