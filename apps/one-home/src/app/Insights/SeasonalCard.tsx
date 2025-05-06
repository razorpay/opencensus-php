import React from 'react';
import { Card, Box, Text, Heading, Skeleton } from '@razorpay/blade/components';
import { staticContent } from './constants';
import { SeasonalCardProp, SeasonalCardBodyProp } from './types';
import { ErrorBoundary } from '@libs/shared-ui';
import { DASHBOARD_PRIORITY_RANKS, DASHBOARD_TEAMS } from '@libs/shared-types';
import ErrorState from '../../components/ErrorBoundary/ErrorState';
const diyaImage = require('../../assets/diya.png');

// TODO: Not for v1
const SeasonalCardContent = ({ isMobile }: { isMobile: boolean }) => {
  return (
    <Box
      display="flex"
      gap="spacing.3"
      flexDirection="column"
      alignContent="center"
      justifyContent="center"
      padding={['spacing.8', 'spacing.0', 'spacing.8', 'spacing.8']}
      paddingRight={{
        base: 'spacing.0',
        m: 'spacing.8',
        l: 'spacing.0',
      }}
      width={{
        base: 'auto',
        l: 'auto',
        xl: '640px',
      }}
      minWidth={{
        base: 'auto',
        m: '256px',
        l: 'auto',
      }}
    >
      {isMobile ? (
        <>
          <Text variant="body" size="small" weight="semibold" color="surface.text.gray.muted">
            During the Diwali month,
          </Text>
          <Text variant="body" size="medium" weight="semibold" color="surface.text.gray.normal">
            Expected revenue increase of 12L, & 30% expected increase in sales.
          </Text>
        </>
      ) : (
        <>
          <Text variant="body" size="large" weight="semibold" color="surface.text.gray.muted">
            During the Diwali month,
          </Text>
          <Heading size="xlarge" weight="semibold" color="surface.text.gray.normal">
            Expected revenue increase of 12L, & 30% expected increase in sales.
          </Heading>
        </>
      )}
    </Box>
  );
};

const SeasonalCardImage = () => {
  return (
    <Box
      display={{ base: 'block', s: 'block', m: 'none', l: 'block' }}
      flexShrink="0"
      width={{
        base: '136px',
        s: '136px',
        m: '0px',
        l: '136px',
        xl: 'calc(100% - 640px)',
      }}
      height="100%"
      backgroundImage={`url(${diyaImage})`}
      backgroundSize="contain"
      backgroundRepeat="no-repeat"
      backgroundPosition="bottom right"
    />
  );
};

const SeasonalCardBody = ({ isMobile, componentData }: SeasonalCardBodyProp) => {
  if (!componentData || componentData?.error) {
    throw componentData && componentData.error
      ? componentData.error
      : new Error('Error fetching data in Seasonal Card');
  }

  return (
    <Box
      display="flex"
      justifyContent="space-between"
      alignItems={{ base: 'center', m: 'end', l: 'center' }}
      overflow="hidden"
    >
      <SeasonalCardContent isMobile={isMobile} />
      <SeasonalCardImage />
    </Box>
  );
};

const SeasonalCard = ({ isLoading, isMobile, componentData }: SeasonalCardProp) => {
  if (isLoading) {
    return (
      <Skeleton
        height={{
          base: '162px',
          s: '162px',
          m: '272px',
          l: '272px',
          xl: '162px',
        }}
        borderRadius="large"
      />
    );
  }

  return (
    <Card
      width={{
        base: '100%',
        s: '100%',
        m: 'calc((100% - 16px) / 2)',
        l: 'calc((100% - 16px) / 2)',
        xl: '100%',
      }}
      borderRadius="large"
      backgroundColor="surface.background.gray.intense"
      elevation="none"
      padding="spacing.0"
      minHeight="100%"
      display="flex"
    >
      <ErrorBoundary
        key="seasonal"
        rank={DASHBOARD_PRIORITY_RANKS.P0}
        team={DASHBOARD_TEAMS.R1_CONNECTED_EXPERIENCE}
        tags={{ module: 'One_Home_Insight_Card' }}
        FallbackComponent={() => (
          <ErrorState title={staticContent.errorText} withBorder={true} borderRadius="large" />
        )}
      >
        <SeasonalCardBody isMobile={isMobile} componentData={componentData} />
      </ErrorBoundary>
    </Card>
  );
};

export default SeasonalCard;
