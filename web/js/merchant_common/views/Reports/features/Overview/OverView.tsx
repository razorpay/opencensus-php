import React, { useMemo, useState, useEffect } from 'react';
import { sortCardsByReportType } from 'merchant_common/views/Reports/utils/commonUtils';
import {
  ActionListItem,
  Dropdown,
  DropdownOverlay,
  Heading,
  SelectInput,
  ActionList,
} from 'merchant_common/views/Reports/components';
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
  DropdownWrapper,
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
  showNotification,
  dashboardType,
  isOverviewRecentsFilterEnabled,
}: OverViewPropsType): JSX.Element => {
  const overviewFilterDropdownOptions = useMemo(
    () => overviewConfigFilterOptions(isOverviewRecentsFilterEnabled),
    [isOverviewRecentsFilterEnabled],
  );
  const [filter, setFilter] = useState(overviewFilterDropdownOptions[0].value);
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
      case filter === 'all' && isAllConfigLoaded:
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
      <OverviewBanner loading={!isAllConfigLoaded} />
      <AccessabilityToolbar theme={theme}>
        <DropdownWrapper>
          <Dropdown selectionType="single">
            <SelectInput
              label="Filter:"
              labelPosition="top"
              onChange={({ values }) =>
                handleFilterDropdownSelection(overviewFilterDropdownOptions[+values[0]])
              }
              placeholder="Choose A Filter"
              validationState="none"
              value={overviewFilterDropdownOptions
                .findIndex((refFilter) => refFilter.value === filter)
                .toString()}
            />
            <DropdownOverlay>
              <ActionList
                options={overviewFilterDropdownOptions}
                itemComponent={({ data: { label, value }, index }) => (
                  <ActionListItem
                    key={value}
                    title={label}
                    value={index.toString()}
                    testID={label}
                  />
                )}
              />
            </DropdownOverlay>
          </Dropdown>
        </DropdownWrapper>
      </AccessabilityToolbar>
      <CardContainer theme={theme}>{renderOverviewCards()}</CardContainer>
    </>
  );
};
