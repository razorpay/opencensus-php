import React, { useMemo, useState, useEffect } from 'react';
import { sortCardsByReportType } from 'merchant_common/views/Reports/utils/commonUtils';
import { Dropdown, Heading, Text } from 'merchant_common/views/Reports/components';
import { OverViewPropsType } from 'merchant_common/views/Reports/features/Overview/types';
import { Card } from 'merchant_common/views/Reports/features/Overview/components/Card';
import { CardSkeleton } from 'merchant_common/views/Reports/features/Overview/components/Card/Skeleton';
import { OverviewBanner } from 'merchant_common/views/Reports/features/Overview/components/OverviewBanner';
import { overviewConfigFilterOptions } from 'merchant_common/views/Reports/features/Overview/constants/common';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import { getRecentConfigs } from 'merchant_common/views/Reports/api/overview';
import {
  AccessabilityToolbar,
  CardContainer,
  CardsWrapper,
  DropdownLabel,
  ReportTypeHeader,
  ReportTypeWrapper,
} from './styled';
import { REPORT_OVERVIEW_LOADING_SKELETONS_COUNT } from 'merchant_common/views/Reports/constants';
import { trackOverviewSection } from 'merchant_common/views/Reports/configs/analytics.config';

export const OverviewSection = ({
  allReportConfigs,
  recentlyUsedReportConfigs,
  isAllConfigLoaded,
  isRecentlyUsedConfigLoaded,
  fetchRecentlyUsedConfigsFailed,
  fetchRecentlyUsedConfigsSuccess,
  handleOverviewLoading,
  refDashboardConfig: { headers, basePath, parseConfigs, customConfigs },
  history,
  showNotification,
  dashboardType,
}: OverViewPropsType): JSX.Element => {
  const [filter, setFilter] = useState('');
  const { theme } = useTheme();

  const cardsSortedByReportType: any = useMemo(
    () => sortCardsByReportType(allReportConfigs),
    [allReportConfigs],
  );

  const handleFilterDropdownSelection = async ({ label, value }) => {
    trackOverviewSection({
      actionName: 'Overview Filter Interaction',
      properties: {
        interaction_type: 'select',
        selectedFilter: label,
      },
      dashboardType,
    });

    setFilter(value);
    if (value === 'recents') {
      try {
        handleOverviewLoading({
          key: 'recentConfigs',
          state: true,
        });
        const a = await getRecentConfigs(headers);
        if (a?.data?.items) {
          fetchRecentlyUsedConfigsSuccess({ configs: parseConfigs(a.data.items) });
        } else {
          throw new Error('Invalid Response.');
        }
      } catch {
        showNotification({
          type: 'error',
          message: 'Unable to fetch recently used reports at this moment, please try again later.',
        });
        fetchRecentlyUsedConfigsFailed();
      }
    }
  };

  const renderOverviewCards = () => {
    const parsedAdditionalConfigs = parseConfigs(customConfigs);
    switch (true) {
      case !Boolean(filter) && isAllConfigLoaded:
        return (
          <>
            <CardsWrapper aria-label="All Configs Container" theme={theme}>
              {allReportConfigs?.map((data) => (
                <Card key={data.id} data={data} linkBasePath={basePath} />
              ))}
            </CardsWrapper>
            {parsedAdditionalConfigs.length ? (
              <ReportTypeWrapper theme={theme}>
                <ReportTypeHeader theme={theme}>
                  <Heading weight="bold" type="normal" variant="regular">
                    OTHER REPORTS
                  </Heading>
                </ReportTypeHeader>
                <CardsWrapper aria-label="Other Reports" theme={theme}>
                  {parsedAdditionalConfigs.map((config) => {
                    return <Card data={config} key={config.id} linkBasePath={basePath} />;
                  })}
                </CardsWrapper>
              </ReportTypeWrapper>
            ) : null}
          </>
        );
      case filter === 'recents' && isRecentlyUsedConfigLoaded:
        return (
          <CardsWrapper aria-label="Recently Used Configs Container" theme={theme}>
            {recentlyUsedReportConfigs?.map((data) => (
              <Card key={data.id} data={data} linkBasePath={basePath} />
            ))}
          </CardsWrapper>
        );
      case filter === 'report_type' && isAllConfigLoaded:
        return (
          <>
            {cardsSortedByReportType?.map(([type, configs], index) => {
              return (
                <ReportTypeWrapper key={type} index={index} theme={theme}>
                  <ReportTypeHeader theme={theme} index={index}>
                    <Heading weight="bold" type="normal" variant="regular">
                      {type.toUpperCase()}
                    </Heading>
                  </ReportTypeHeader>
                  <CardsWrapper
                    aria-label={`Configs Ordered By ${type.toUpperCase()}`}
                    theme={theme}
                  >
                    {configs.map((config) => {
                      return <Card data={config} key={config.id} linkBasePath={basePath} />;
                    })}
                  </CardsWrapper>
                </ReportTypeWrapper>
              );
            })}
            {parsedAdditionalConfigs.length ? (
              <ReportTypeWrapper theme={theme}>
                <ReportTypeHeader theme={theme}>
                  <Heading weight="bold" type="normal" variant="regular">
                    OTHER REPORTS
                  </Heading>
                </ReportTypeHeader>
                <CardsWrapper aria-label="Other Reports" theme={theme}>
                  {parsedAdditionalConfigs.map((config) => {
                    return <Card data={config} key={config.id} linkBasePath={basePath} />;
                  })}
                </CardsWrapper>
              </ReportTypeWrapper>
            ) : null}
          </>
        );
      default:
        return (
          <CardsWrapper aria-label="Configs Loading Skeleton Container" theme={theme}>
            {Array(REPORT_OVERVIEW_LOADING_SKELETONS_COUNT)
              .fill(1)
              .map((_, index) => (
                <CardSkeleton key={index.toString()} />
              ))}
          </CardsWrapper>
        );
    }
  };

  useEffect(() => {
    trackOverviewSection({
      actionName: 'Loaded',
      properties: {
        is_configs_loaded: isAllConfigLoaded,
      },
      dashboardType,
    });
  }, [isAllConfigLoaded]);

  return (
    <>
      <OverviewBanner loading={!isAllConfigLoaded} history={history} />
      <AccessabilityToolbar theme={theme}>
        <DropdownLabel>
          <Text type="subtle" variant="body">
            Filter:
          </Text>
        </DropdownLabel>
        <Dropdown
          value={overviewConfigFilterOptions.find((item) => item.value === filter)}
          options={overviewConfigFilterOptions}
          labelKey="label"
          defaultValue={overviewConfigFilterOptions[0]}
          onChange={(e) => e && handleFilterDropdownSelection(e)}
          ariaLabelBy="Choose A Config Filter"
        />
      </AccessabilityToolbar>
      <CardContainer theme={theme}>{renderOverviewCards()}</CardContainer>
    </>
  );
};

// ..
