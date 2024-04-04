import React, { useState, Dispatch, SetStateAction } from 'react';
import { Box, Heading, Text } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import {
  OVERVIEW_HEADER,
  DEFAULT_OVERVIEW_TAB,
  OVERVIEW_TABS,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/EntityOverview/constants';
import { fetchRatios } from 'merchant/views/RiskAndFraud/RiskAnalytics/services';
import { AnalyticsEntity, Ratios } from 'merchant/views/RiskAndFraud/RiskAnalytics/types';

import OverviewCard from './OverviewCard';
import { getRatioPayload } from './utils';
import { trackEvent } from '../../common/trackEvents';

interface EntityOverviewProps {
  setRatio: Dispatch<SetStateAction<Ratios>>;
}

const EntityOverview: React.FC<EntityOverviewProps> = ({ setRatio }) => {
  const [selectedTab, setSelectedTab] = useState(DEFAULT_OVERVIEW_TAB);
  const { startDate, endDate } = getRatioPayload();
  const tabs = Object.keys(OVERVIEW_TABS);

  const { data } = useQuery({
    queryKey: ['Ratios', { startDate, endDate }],
    queryFn: () => fetchRatios({ startDate, endDate }),
    cacheTime: 15 * 60 * 1000, // Cache data for 15 minutes
    staleTime: 15 * 60 * 1000, // Data remains fresh for 15 minutes
    retry: false,
    refetchOnWindowFocus: false,
    onSuccess: (ratios: Ratios) => setRatio(ratios),
  });

  const handleTabChange = (event: React.MouseEvent<HTMLButtonElement>) => {
    const targetId =
      (event.target as HTMLButtonElement).id || (event.currentTarget as HTMLButtonElement).id;
    setSelectedTab(targetId);
    trackEvent({
      objectName: 'Top Level metrics',
      properties: { tabName: targetId },
    });
  };

  return (
    <Box
      display="flex"
      flexDirection="column"
      padding="spacing.5"
      backgroundColor="surface.background.gray.intense"
      marginTop="spacing.5"
    >
      <Box
        display="flex"
        alignItems="center"
        justifyContent="space-between"
        marginBottom="spacing.7"
      >
        <Heading size="small">{OVERVIEW_HEADER}</Heading>
        <Text variant="caption">Last 6 months</Text>
      </Box>
      <Box
        display="flex"
        flexDirection="row"
        borderColor="surface.border.gray.subtle"
        borderRadius="medium"
      >
        {tabs.map((entity) => {
          return (
            <OverviewCard
              key={entity}
              selectedTab={selectedTab}
              entity={entity as AnalyticsEntity}
              ratios={data}
              handleTabChange={handleTabChange}
            />
          );
        })}
      </Box>
    </Box>
  );
};

export default EntityOverview;
