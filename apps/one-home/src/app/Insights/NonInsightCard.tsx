import React, { useEffect } from 'react';
import { Box, Button, Card, Heading, Skeleton, Text } from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';
import useOneHomeAnalytics from '../../hooks/useOneHomeAnalytics';
import { otherInsightsImageMapping, staticContent } from './constants';
import {
  NonInsightCardBodyProp,
  NonInsightCardImageProp,
  NonInsightCardProp,
  NonInsightCardType,
  NonInsightContentProp,
  Action,
} from './types';
import { ErrorBoundary } from '@libs/shared-ui';
import { DASHBOARD_PRIORITY_RANKS, DASHBOARD_TEAMS } from '@libs/shared-types';
import ErrorState from '../../components/ErrorBoundary/ErrorState';
import { getAbsolutePath } from './utils';

const NonInsightCardContent = ({ isMobile, componentData, analytics }: NonInsightContentProp) => {
  const actionsData = componentData.actions ?? [];
  const navigate = useNavigate();
  const { trackOneHomeAnalytics } = useOneHomeAnalytics();

  const trackCTA = ({ title }: { title: string }) => {
    const properties = {
      ...analytics,
      buttonName: title,
    };

    trackOneHomeAnalytics({
      objectName: 'Ucs Link',
      actionName: 'Clicked',
      properties: { ...properties },
    });
  };

  const handleNavigate = (actionsData: Action) => {
    if (actionsData?.action_params?.path) {
      // Ensures paths are absolute since useNavigate treats relative paths based on the current route.
      navigate(getAbsolutePath(actionsData?.action_params?.path));
    } else {
      window.open(actionsData?.action_params?.url, '_blank');
    }
    trackCTA({ title: actionsData?.title });
  };

  return (
    <Box
      padding={['spacing.8', 'spacing.0', 'spacing.8', 'spacing.8']}
      display="flex"
      flexDirection="column"
      justifyContent="space-between"
      gap="spacing.5"
      alignItems="flex-start"
    >
      <Box>
        {isMobile ? (
          <Text variant="body" size="small" weight="semibold" color="surface.text.gray.muted">
            {componentData.title}
          </Text>
        ) : (
          <Text variant="body" size="large" weight="semibold" color="surface.text.gray.muted">
            {componentData.title}
          </Text>
        )}
        <Heading size="xlarge" weight="semibold" color="surface.text.gray.normal">
          {componentData.description}
        </Heading>
      </Box>
      <Box>
        {actionsData[0]?.action === 'navigate' ? (
          <Button
            variant={actionsData[0]?.properties?.variant}
            color="primary"
            size="small"
            isFullWidth={false}
            onClick={() => handleNavigate(actionsData[0])}
            accessibilityLabel={staticContent.nonInsightBtnLabel}
          >
            {actionsData[0]?.title}
          </Button>
        ) : (
          <Text variant="caption" size="medium" weight="regular" color="surface.text.gray.muted">
            {staticContent.comingSoonText}
          </Text>
        )}
      </Box>
    </Box>
  );
};

const NonInsightCardImage = ({ cardType }: NonInsightCardImageProp) => {
  return (
    <Box
      minWidth="136px"
      height="100%"
      backgroundImage={`url(${otherInsightsImageMapping[cardType as NonInsightCardType]})`}
      backgroundSize="contain"
      backgroundRepeat="no-repeat"
      backgroundPosition="bottom right"
    />
  );
};

const NonInsightCardBody = ({
  isMobile,
  componentData,
  cardType,
  analytics,
}: NonInsightCardBodyProp) => {
  const { trackOneHomeAnalytics } = useOneHomeAnalytics();
  if (!componentData || componentData?.error) {
    throw componentData && componentData.error
      ? componentData.error
      : new Error('Error fetching data in NonInsightCard');
  }

  useEffect(() => {
    if (!componentData || componentData?.error) return;

    trackOneHomeAnalytics({
      objectName: 'Ucs Widget',
      actionName: 'Loaded',
      properties: {
        ...analytics,
        items: [componentData?.actions?.map((action) => action.title)],
      },
    });
  }, [componentData]);

  return (
    <Box display="flex" justifyContent="space-between" alignItems="flex-start" height="100%">
      <NonInsightCardContent
        isMobile={isMobile}
        componentData={componentData}
        analytics={analytics}
      />
      <NonInsightCardImage cardType={cardType} />
    </Box>
  );
};

const NonInsightCard = ({
  cardType,
  isLoading,
  isMobile,
  componentData,
  analytics,
}: NonInsightCardProp) => {
  return (
    <Card
      width={{
        base: '100%',
        s: '100%',
        m: 'calc((100% - 16px) / 2)',
        l: 'calc((100% - 16px) / 2)',
        xl: 'calc((100% - 32px) / 3)',
      }}
      borderRadius="medium"
      elevation="none"
      backgroundColor={`${
        isLoading ? 'surface.background.gray.intense' : 'surface.background.gray.subtle'
      }`}
      padding="spacing.0"
      minHeight="100%"
      display="flex"
    >
      {isLoading ? (
        <Skeleton height="272px" borderRadius="medium" testID="onehome-other-insight-skeleton" />
      ) : (
        <ErrorBoundary
          key={cardType}
          rank={DASHBOARD_PRIORITY_RANKS.P0}
          team={DASHBOARD_TEAMS.R1_CONNECTED_EXPERIENCE}
          tags={{ module: 'One_Home_Non_Insight_Card' }}
          FallbackComponent={() => (
            <ErrorState title={staticContent.errorText} withBorder={false} borderRadius="medium" />
          )}
        >
          <NonInsightCardBody
            isMobile={isMobile}
            componentData={componentData}
            cardType={cardType}
            analytics={analytics}
          />
        </ErrorBoundary>
      )}
    </Card>
  );
};

export default NonInsightCard;
