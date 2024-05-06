import React, { useState, Dispatch, SetStateAction } from 'react';
import { Box, Heading, Text } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import {
  OVERVIEW_HEADER,
  DEFAULT_OVERVIEW_TAB,
  OVERVIEW_TABS,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/EntityOverview/constants';
import { fetchRatios } from 'merchant/views/RiskAndFraud/RiskAnalytics/services';
import {
  AnalyticsEntity,
  Ratios,
  SectionRef,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/types';
import { trackEvent } from 'merchant/views/RiskAndFraud/common/trackEvents';

import OverviewCard from './OverviewCard';
import { getRatioPayload } from './utils';

interface EntityOverviewProps {
  setRatio: Dispatch<SetStateAction<Ratios>>;
  sectionRef: { current: SectionRef };
}

const EntityOverview: React.FC<EntityOverviewProps> = ({ setRatio, sectionRef }) => {
  const [selectedTab, setSelectedTab] = useState<AnalyticsEntity>(DEFAULT_OVERVIEW_TAB);
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
    const targetId = (event.target as HTMLButtonElement).id;
    const section = sectionRef?.current?.[targetId];
    if (section) {
      // Get the top position of the target section relative to the viewport
      const offset = section.getBoundingClientRect().top;

      // Scroll position calculation (scrollTop):
      //  - window.scrollY: Current vertical scroll position of the window
      //  - offset: Vertical distance of the target section from the top of the viewport (can be negative)
      //  - 76: Adjust the scroll position to account for the height of the header and margin space
      const scrollTop = window.scrollY + offset - 76;
      window.scrollTo({ top: scrollTop, behavior: 'smooth' });
    }
    setSelectedTab(targetId as AnalyticsEntity);
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
        <Text>For the last 6 months</Text>
      </Box>
      <Box display="flex" flexDirection="row" gap="spacing.3" borderRadius="medium">
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
