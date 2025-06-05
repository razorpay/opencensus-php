import React, { Fragment, useEffect, useState,useMemo } from 'react';
import { useStore } from '@apps/shell/src/client/store/commonStore';
import { DASHBOARD_PRIORITY_RANKS, DASHBOARD_TEAMS } from '@libs/shared-types';
import { ErrorBoundary } from '@libs/shared-ui';
import {
  ArrowRightIcon,
  Box,
  Divider,
  Drawer,
  DrawerBody,
  DrawerHeader,
  Link,
} from '@razorpay/blade/components';
import { useBreakpoint, useTheme } from '@razorpay/blade/utils';
import ErrorState from '../../components/ErrorBoundary/ErrorState';
import SectionHeader from '../../components/SectionHeader/SectionHeader';
import useHorizontalScroll from '../../hooks/useHorizontalScroll';
import useOneHomeAnalytics from '../../hooks/useOneHomeAnalytics';
import { criticalSectionMessages } from './constants';
import CriticalActionCard from './CriticalActionCard';
import CriticalActionsInDrawer from './CriticalActionInDrawer';
import CrticalActionShimmer from './CriticalActionsShimmer';
import useCriticalActionLists from './hooks/useCriticalActionLists';
import useCriticalActions from './hooks/useCriticalActions';
import NoActionMessage from './NoActionMessage';
import StyledScrollContainer from './StyledScrollContainer';
import { CriticalActionComponent, CriticalActionsContentProps } from './types';

const ScrollableView = ({
  isMobile,
  criticalActions,
}: {
  isMobile: boolean;
  criticalActions: CriticalActionComponent[];
}) => {
  const { scrollContainerRef, isScrolledLeft, isScrolledRight } = useHorizontalScroll();

  return (
    <Box width="100%" display="flex">
      <StyledScrollContainer
        ref={scrollContainerRef}
        isScrolledLeft={isScrolledLeft}
        isScrolledRight={isScrolledRight}
        isMobile={isMobile}
      >
        {criticalActions.map((item) => (
          <Box
            key={item.id}
            minWidth={{ base: '280px', m: 'calc(50% - 8px)', xl: 'calc(33% - 8px)' }}
            display="flex"
          >
            <ErrorBoundary
              rank={DASHBOARD_PRIORITY_RANKS.P0}
              team={DASHBOARD_TEAMS.R1_CONNECTED_EXPERIENCE}
              tags={{ module: `One_Home_Critical_Actions_${item.alias}` }}
              FallbackComponent={() => (
                <Box display="flex" whiteSpace="normal">
                  <ErrorState
                    size="medium"
                    title={criticalSectionMessages.error.title}
                    borderRadius="large"
                  />
                </Box>
              )}
            >
              <CriticalActionCard key={item.id} {...item} />
            </ErrorBoundary>
          </Box>
        ))}
      </StyledScrollContainer>
    </Box>
  );
};

const CriticalActionsContent = ({
  criticalActionsData,
  isLoading,
  isMobile,
  error,
  isDrawerOpen,
  onDrawerDismiss,
}: CriticalActionsContentProps) => {
  if (error) {
    throw error;
  }
  const { criticalActionsToShow, drawerCriticalActions } = useCriticalActionLists(
    criticalActionsData,
    isMobile,
  );
  const { criticalActionDataLength } = useCriticalActions();
  const { trackOneHomeAnalytics } = useOneHomeAnalytics();

  useEffect(() => {
    if (!isLoading) {
      trackOnLoad();
    }
  }, [isLoading]);

  const trackOnLoad = () => {
    const items = drawerCriticalActions.map((action) => action.title);
    const properties = {
      title: criticalSectionMessages.sectionTitle,
      widgetId: criticalSectionMessages.widgetId,
      criticalActionsCount: criticalActionDataLength,
      items,
    };
    trackOneHomeAnalytics({
      objectName: 'Ucs Widget',
      actionName: 'Loaded',
      properties: { ...properties },
    });
  };

  const renderCriticalActionCards = () => {
    return criticalActionsToShow.map((item: CriticalActionComponent) => (
      <Box
        key={item.id}
        minWidth={{
          base: '280px',
          m: 'calc(50% - 8px)',
          xl: 'calc(33% - 8px)',
        }}
        display="flex"
      >
        <ErrorBoundary
          rank={DASHBOARD_PRIORITY_RANKS.P0}
          team={DASHBOARD_TEAMS.R1_CONNECTED_EXPERIENCE}
          tags={{ module: `One_Home_Critical_Actions_${item.alias}` }}
          FallbackComponent={() => (
            <Box display="flex" whiteSpace="normal">
              <ErrorState
                size="medium"
                title={criticalSectionMessages.error.title}
                borderRadius="large"
                withBorder
              />
            </Box>
          )}
        >
          <CriticalActionCard key={item.id} {...item} />
        </ErrorBoundary>
      </Box>
    ));
  };

  return (
    <Box>
      <Fragment>
        {isMobile ? (
          <ScrollableView isMobile={isMobile} criticalActions={criticalActionsToShow} />
        ) : (
          <Box display="flex" gap="spacing.5" alignItems="stretch">
            {renderCriticalActionCards()}
          </Box>
        )}

        <Drawer isOpen={isDrawerOpen} onDismiss={onDrawerDismiss}>
          <DrawerHeader title="Critical actions" />
          <DrawerBody>
            <Box display="flex" flexDirection="column">
              {drawerCriticalActions.map((item) => (
                <ErrorBoundary
                  rank={DASHBOARD_PRIORITY_RANKS.P0}
                  team={DASHBOARD_TEAMS.R1_CONNECTED_EXPERIENCE}
                  tags={{ module: `One_Home_Critical_Actions_Drawer_${item.alias}` }}
                  FallbackComponent={() => (
                    <Box>
                      <ErrorState
                        size="medium"
                        title={criticalSectionMessages.error.title}
                        borderRadius="large"
                      />
                      <Box marginY="spacing.5">
                        <Divider
                          width="380px"
                          height="0.5px"
                          orientation="horizontal"
                          dividerStyle="solid"
                        />
                      </Box>
                    </Box>
                  )}
                >
                  <CriticalActionsInDrawer criticalActioncomponent={item} />
                </ErrorBoundary>
              ))}
            </Box>
          </DrawerBody>
        </Drawer>
      </Fragment>
    </Box>
  );
};

const MAX_CRITICAL_ACTIONS = Number.MAX_SAFE_INTEGER;
const breakpointVisibilityMap = {
  base: MAX_CRITICAL_ACTIONS,
  m: 2,
  l: 2,
  xl: 3,
};

const CriticalActions: React.FC = () => {
  const { user } = useStore((state) => state['session']);
  const { criticalActionsData, isLoading, error } = useCriticalActions();
  const { trackOneHomeAnalytics } = useOneHomeAnalytics();
  const { theme } = useTheme();
  const { matchedDeviceType,matchedBreakpoint = "base" } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [criticalActionsDisplay, setCriticalActionsDisplay] = useState({
    showCriticalSection: true,
    showNoActionAlert: false,
  });

  const isMobile = matchedDeviceType === 'mobile';
  const { criticalActionsList, drawerCriticalActions } = useCriticalActionLists(
    criticalActionsData,
    isMobile,
  );
  
  const visibleCriticalActionsItems = breakpointVisibilityMap[matchedBreakpoint] ?? MAX_CRITICAL_ACTIONS;

  const isShowAllVisible = useMemo(() => {
    return !isMobile && !error && drawerCriticalActions.length > visibleCriticalActionsItems;
  }, [isMobile, error, drawerCriticalActions.length, visibleCriticalActionsItems]);

  useEffect(() => {
    if (!criticalActionsList.length && !isLoading && !error) {
      setCriticalActionsDisplay({
        showCriticalSection: false,
        showNoActionAlert: true,
      });
    }
  }, [isLoading, criticalActionsList.length]);

  useEffect(() => {
    if (error) {
      trackOnLoadError();
    }
  }, [error]);

  const trackOnLoadError = () => {
    trackOneHomeAnalytics({
      objectName: 'Ucs Widget',
      actionName: 'Loaded',
      properties: {
        title: criticalSectionMessages.sectionTitle,
        itemName: 'Oops! An error occured.',
      },
    });
  };

  const trackShowAllClick = () => {
    const analyticsProps = {
      title: criticalSectionMessages.sectionTitle,
      widgetId: criticalSectionMessages.widgetId,
    };
    trackOneHomeAnalytics({
      objectName: 'ShowAll Button',
      actionName: 'Clicked',
      properties: { ...analyticsProps },
    });
  };

  const handleShowAllClick = () => {
    setIsDrawerOpen(!isDrawerOpen);
    trackShowAllClick();
  };

  return isLoading ? (
    <CrticalActionShimmer />
  ) : (
    <>
      {criticalActionsDisplay.showCriticalSection ? (
        <Box width="100%" display="flex" flexDirection="column" overflow="hidden">
          <Box>
            <SectionHeader>
              <SectionHeader.Title>
                {criticalActionsData?.title || criticalSectionMessages.sectionTitle}
              </SectionHeader.Title>
              {isShowAllVisible ? (
                <SectionHeader.Content>
                  <SectionHeader.Actions>
                    <Link
                      variant="anchor"
                      color="primary"
                      size="large"
                      onClick={handleShowAllClick}
                      icon={ArrowRightIcon}
                      iconPosition="right"
                    >
                      Show all
                    </Link>
                  </SectionHeader.Actions>
                </SectionHeader.Content>
              ) : null}
            </SectionHeader>

            <ErrorBoundary
              rank={DASHBOARD_PRIORITY_RANKS.P0}
              team={DASHBOARD_TEAMS.R1_CONNECTED_EXPERIENCE}
              tags={{ module: 'One_Home_Critical_Actions' }}
              FallbackComponent={() => (
                <ErrorState
                  size="medium"
                  title={criticalSectionMessages.error.title}
                  borderRadius="large"
                  withBorder
                />
              )}
            >
              <CriticalActionsContent
                criticalActionsData={criticalActionsData}
                isLoading={isLoading}
                error={error}
                isMobile={isMobile}
                isDrawerOpen={isDrawerOpen}
                onDrawerDismiss={() => setIsDrawerOpen(false)}
              />
            </ErrorBoundary>
          </Box>
        </Box>
      ) : null}

      {criticalActionsDisplay.showNoActionAlert ? <NoActionMessage user={user} /> : null}
    </>
  );
};

export default CriticalActions;
