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
  MenuIcon,
  Button,
  UserIcon,
} from '@razorpay/blade/components';
import { makeSize } from '@razorpay/blade/utils';
import { useStore } from 'shell/commonStore';
import type { TabNavItemProps, SideNavProps } from '@razorpay/blade/components';
import ExploreItem from 'merchant/components/NavigationLayout/TopNavigation/components/ExploreItem';
import RazorpayLogo from '../../TopNavigation/RazorpayLogo';

const topNavShimmerListItems = [
  {
    href: '/payments',
    title: 'Payments',
    icon: AcceptPaymentsIcon,
    description: 'Accept payments for your business seamlessly',
  },
  {
    href: '/banking',
    title: 'Banking',
    icon: RazorpayXIcon,
    description: 'Supercharged banking for your business',
  },
  {
    href: '/payroll',
    title: 'Payroll',
    icon: RazorpayxPayrollIcon,
    description: 'Payroll process made simple',
  },
  {
    href: '/partners',
    title: 'Partners',
    icon: UsersIcon,
    description: "India's most comprehensive partner program for payments and beyond",
  },
  {
    href: '/rize',
    title: 'Rize',
    icon: AwardIcon,
    isAlwaysOverflowing: true,
    description: 'An exclusive program that empowers founders by fostering vibrant communities',
  },
];

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
  const shimmerListItems = new Array(4).fill(0).map((_, i) => i);
  const shimmerFooterListItems = new Array(2).fill(0).map((_, i) => i);
  return (
    <SideNav isOpen={isOpen} onDismiss={onDismiss} position="absolute">
      <SideNavBody>
        <>
          {shimmerListItems.map((_, i) => (
            <NavLinkShimmer key={`section_1_shimmer_item_${i}`} />
          ))}

          <NavSectionShimmer />

          {shimmerListItems.map((_, i) => (
            <NavLinkShimmer key={`section_2_shimmer_item_${i}`} />
          ))}
        </>
      </SideNavBody>
      <SideNavFooter>
        <Box paddingBottom={'spacing.11'}>
          {shimmerFooterListItems.map((_, i) => (
            <NavLinkShimmer key={`footer-shimmer_item_${i}`} />
          ))}
        </Box>
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

const DashboardBackground = styled.div(() => {
  return {
    height: '100vh',
    //background: 'radial-gradient(94.74% 64.44% at 29.03% 15.17%, #FFFFFF 0%, #90A5BB 100%)',
  };
});

export const TopNavigationSkeleton: React.FC = () => {
  const { platform } = useTheme();

  const isMobile = platform === 'onMobile';
  const user = useStore((state) => state.session.user);

  return isMobile ? (
    <>
      <TopNav zIndex="101" backgroundColor="surface.background.gray.intense">
        <Button
          size="medium"
          variant="tertiary"
          icon={MenuIcon}
          isDisabled
          marginRight="spacing.4"
        />
        <BladeLink icon={HomeIcon} size="medium" onClick={(e) => e.preventDefault()}>
          Home
        </BladeLink>
        <Avatar size="medium" icon={UserIcon} variant="square" name={user?.name} />
      </TopNav>
      <Box paddingX={'spacing.3'} backgroundColor="surface.background.gray.intense">
        <Divider />
      </Box>
    </>
  ) : (
    <TopNav zIndex="101">
      <TopNavBrand>
        <RazorpayLogo />
      </TopNavBrand>
      <TopNavContent>
        <TabNav items={topNavShimmerListItems}>
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
                    <TabNavItem title={'More'} trailing={<ChevronDownIcon />} isActive={false} />
                    <MenuOverlay>
                      <MenuHeader title="Products for you" />
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
        <SearchInput placeholder="Search in payments" accessibilityLabel="Search Across Razorpay" />

        <Menu openInteraction="click">
          <Avatar size="medium" icon={UserIcon} variant="square" name={user?.name} />
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
  );
};

const Skeleton = (): React.ReactElement => {
  const { platform } = useTheme();

  const isMobile = platform === 'onMobile';

  return (
    <DashboardBackground>
      <Box
        overflow="hidden"
        position="relative"
        //borderRadius="large"
        borderTopRightRadius="none"
        borderTopLeftRadius="medium"
        borderBottomRightRadius="none"
        height="100%"
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
    </DashboardBackground>
  );
};

export { Skeleton };
