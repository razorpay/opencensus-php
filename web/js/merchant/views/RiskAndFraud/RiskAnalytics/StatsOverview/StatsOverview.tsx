import React, { Fragment } from 'react';
import {
  Box,
  Text,
  Title,
  Heading,
  Tooltip,
  TooltipInteractiveWrapper,
  InfoIcon,
  Divider,
  BarChartAltIcon,
} from '@razorpay/blade/components';
import { getCurrencySymbol as i18nifyGetCurrencySymbol } from '@razorpay/i18nify-js/currency';
import isEqual from 'lodash/isEqual';

import StatsLoader from './StatsLoader';
import { STATS_CONFIG_MAPPING } from './constants';
import { StatsConfig, StatsOverviewProps } from './types';

const StatsOverview = (props: StatsOverviewProps) => {
  const { isLoading, ratios, entity, metric, stats } = props;
  const statsConfig: StatsConfig[] = STATS_CONFIG_MAPPING[entity]?.[metric] ?? [];

  return (
    <Box
      testID="stats-overview"
      display="flex"
      flexDirection="row"
      paddingTop="spacing.7"
      paddingBottom="spacing.7"
    >
      {isLoading
        ? Array.from({ length: 4 }).map((_, index) => <StatsLoader key={index} />)
        : statsConfig.map(
            ({ key: statsKey, label, tooltip, format, comparisionKey, additionalInfo }, index) => {
              const showThresholdText =
                comparisionKey &&
                stats?.[statsKey] &&
                ratios?.[comparisionKey] &&
                stats[statsKey] > ratios[comparisionKey] &&
                additionalInfo;

              const totalValue = stats?.[statsKey]?.value ?? '0';
              const decimalValue = stats?.[statsKey]?.decimal ?? '00';
              const entityRatio = stats?.[statsKey] ?? '0';
              const entityCount = stats?.[statsKey] ?? '00';

              return (
                <Box
                  key={statsKey}
                  display="flex"
                  flexDirection="row"
                  flex="1"
                  paddingRight="spacing.7"
                >
                  <Box display="flex" flexDirection="column" flex="1" paddingTop="spacing.3">
                    <Box
                      display="flex"
                      flexDirection="row"
                      alignItems="center"
                      marginBottom="spacing.3"
                    >
                      <Text
                        testID="stats-label"
                        size="medium"
                        weight="bold"
                        type="subtle"
                        marginRight="spacing.2"
                        marginBottom="spacing.2"
                      >
                        {label}
                      </Text>
                      <Tooltip content={tooltip} placement="top">
                        <TooltipInteractiveWrapper>
                          <InfoIcon size="small" />
                        </TooltipInteractiveWrapper>
                      </Tooltip>
                    </Box>
                    <Box>
                      {format === 'currency' ? (
                        <Box
                          testID={statsKey}
                          display="flex"
                          flexDirection="row"
                          alignItems="baseline"
                        >
                          <Heading size="small" type="muted" marginRight="spacing.1">
                            {i18nifyGetCurrencySymbol('INR')}
                          </Heading>
                          <Title>{totalValue}</Title>
                          <Heading weight="regular" type="muted" size="medium">
                            .{decimalValue}
                          </Heading>
                        </Box>
                      ) : format === 'percentage' ? (
                        <Fragment>
                          <Box
                            testID={statsKey}
                            display="flex"
                            flexDirection="row"
                            gap="spacing.1"
                            alignItems="baseline"
                          >
                            <Title>{entityRatio}</Title>
                            <Heading weight="regular" type="muted" size="medium">
                              %
                            </Heading>
                          </Box>
                          {showThresholdText && (
                            <Box
                              testID="entity_ratio_threshold"
                              display="flex"
                              flexDirection="row"
                              alignItems="center"
                              gap="spacing.2"
                            >
                              <BarChartAltIcon
                                size="small"
                                color="feedback.icon.negative.lowContrast"
                              />
                              <Text size="small" color="feedback.text.negative.lowContrast">
                                {additionalInfo}
                              </Text>
                            </Box>
                          )}
                        </Fragment>
                      ) : (
                        <Title testID={statsKey}>{entityCount}</Title>
                      )}
                    </Box>
                  </Box>
                  {index < statsConfig.length - 1 && (
                    <Divider
                      testID="stats-divider"
                      orientation="vertical"
                      contrast="low"
                      thickness="thinner"
                    />
                  )}
                </Box>
              );
            },
          )}
    </Box>
  );
};

export default React.memo(StatsOverview, (prevProps, nextProps) => {
  // Only re-render if metric or any property in stats changes
  return (
    prevProps.isLoading === nextProps.isLoading &&
    prevProps.metric === nextProps.metric &&
    isEqual(prevProps.stats, nextProps.stats)
  );
});
