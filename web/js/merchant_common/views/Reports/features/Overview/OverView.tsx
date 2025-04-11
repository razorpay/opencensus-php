import React, { useMemo, useState, useEffect } from 'react';
import { sortCardsByReportType } from 'merchant_common/views/Reports/utils/commonUtils';
import {
  ActionListItem,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  Divider,
  Text,
  Link,
  PlusIcon,
  Box,
} from 'merchant_common/views/Reports/components';

import { useBladeBreakpoints } from 'merchant/views/POS/hooks';
import { OverViewPropsType } from 'merchant_common/views/Reports/features/Overview/types';
import { OverviewCard as Card } from 'merchant_common/views/Reports/features/Overview/components/Card/OverviewCard';
import { CardSkeleton } from 'merchant_common/views/Reports/features/Overview/components/Card/Skeleton';
import { OverviewBanner } from 'merchant_common/views/Reports/features/Overview/components/OverviewBanner';
import { overviewConfigFilterOptions } from 'merchant_common/views/Reports/features/Overview/constants/common';
import { useTheme, useReportsSplitzExperiments } from 'merchant_common/views/Reports/hooks';
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
import { CreateConfigModel } from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/CreateConfigModel';
import {
  maxCustomReportLimitReached,
  customReportsLimit,
  adminGeneratedReportIds,
  deviceRestrictionMessages,
} from 'merchant_common/views/Reports/components/ReportModal/components/CreateConfigModel/utils/constants';
import { useCreateConfigModal } from '../../components/ReportModal/components/CreateConfigModel/store/createConfigModalStore';

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
  isBillMeMerchantOnly,
}: OverViewPropsType): JSX.Element => {
  const { isOverviewRecentsFilterEnabled, isReportsSelfServeEnabled } =
    useReportsSplitzExperiments();
  const overviewFilterDropdownOptions = useMemo(
    () => overviewConfigFilterOptions(isOverviewRecentsFilterEnabled, isReportsSelfServeEnabled),
    [isOverviewRecentsFilterEnabled],
  );
  const [filter, setFilter] = useState(overviewFilterDropdownOptions[0].value);
  const { theme } = useTheme();
  const cardsSortedByReportType: any = useMemo(
    () => sortCardsByReportType(allReportConfigs),
    [allReportConfigs],
  );
  const [isOpen, setIsOpen] = useState<boolean>(false);
  const standardConfigs = useCreateConfigModal((state) => state.standardConfigs);
  const userConfigs = useCreateConfigModal((state) => state.userConfigs);
  const { isDesktop } = useBladeBreakpoints();

  const handleMaxReportLimit = () => {
    if (!isDesktop) {
      showNotification({
        type: 'error',
        message: deviceRestrictionMessages.Custom,
      });
    } else if (userConfigs.length >= customReportsLimit) {
      showNotification({
        type: 'error',
        message: maxCustomReportLimitReached,
      });
    } else {
      setIsOpen((open) => !open);
    }
  };

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
        const a: any = await getRecentConfigs(headers);
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
                  <Text weight="semibold" size="large" color="surface.text.gray.normal">
                    OTHER REPORTS
                  </Text>
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
                    <Text weight="semibold" size="large" color="surface.text.gray.normal">
                      {type.toUpperCase()}
                    </Text>
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
                  <Text weight="semibold" size="large" color="surface.text.gray.normal">
                    OTHER REPORTS
                  </Text>
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
      case filter === 'custom_reports' && isAllConfigLoaded:
        return (
          <>
            {sortCardsByReportType(userConfigs)?.map(([type, configs], index) => {
              return (
                <ReportTypeWrapper key={type} index={index} theme={theme} data-lol="true">
                  <ReportTypeHeader theme={theme} index={index}>
                    <Text weight="semibold" size="large" color="surface.text.gray.normal">
                      {type.toUpperCase()}
                    </Text>
                  </ReportTypeHeader>
                  <CardsWrapper
                    aria-label={`Custom Configs Ordered By ${type.toUpperCase()}`}
                    theme={theme}
                  >
                    {configs.map((config) => {
                      return (
                        <Card
                          data={config}
                          key={config.id}
                          linkBasePath={basePath}
                          isCustomConfig={true}
                        />
                      );
                    })}
                  </CardsWrapper>
                </ReportTypeWrapper>
              );
            })}
          </>
        );
      case filter === 'standard_reports' && isAllConfigLoaded:
        return (
          <>
            {sortCardsByReportType(standardConfigs)?.map(([type, configs], index) => {
              return (
                <ReportTypeWrapper key={type} index={index} theme={theme}>
                  <ReportTypeHeader theme={theme} index={index}>
                    <Text weight="semibold" size="large" color="surface.text.gray.normal">
                      {type.toUpperCase()}
                    </Text>
                  </ReportTypeHeader>
                  <CardsWrapper
                    aria-label={`Standard Configs Ordered By ${type.toUpperCase()}`}
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
                  <Text weight="semibold" size="large" color="surface.text.gray.normal">
                    OTHER REPORTS
                  </Text>
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
      case filter === 'show_all_reports' && isAllConfigLoaded:
        return (
          <>
            {cardsSortedByReportType?.map(([type, configs], index) => {
              return (
                <ReportTypeWrapper key={type} index={index} theme={theme}>
                  <ReportTypeHeader theme={theme} index={index}>
                    <Text weight="semibold" size="large" color="surface.text.gray.normal">
                      {type.toUpperCase()}
                    </Text>
                  </ReportTypeHeader>
                  <CardsWrapper
                    aria-label={`Standard Configs Ordered By ${type.toUpperCase()}`}
                    theme={theme}
                  >
                    {configs.map((config) => {
                      const isCustomConfig = !adminGeneratedReportIds.includes(config.consumer);
                      return (
                        <Card
                          data={config}
                          key={config.id}
                          linkBasePath={basePath}
                          isCustomConfig={isCustomConfig}
                        />
                      );
                    })}
                  </CardsWrapper>
                </ReportTypeWrapper>
              );
            })}
            {parsedAdditionalConfigs.length ? (
              <ReportTypeWrapper theme={theme}>
                <ReportTypeHeader theme={theme}>
                  <Text weight="semibold" size="large" color="surface.text.gray.normal">
                    OTHER REPORTS
                  </Text>
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
      <AccessabilityToolbar theme={theme} isBillMeMerchant={isBillMeMerchantOnly}>
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

        {isReportsSelfServeEnabled ? (
          <Box
            display="flex"
            justifyContent="flex-end"
            width="100%"
            marginTop="30px"
            marginRight="30px"
          >
            <Link
              isDisabled={!isAllConfigLoaded}
              icon={PlusIcon}
              size="large"
              variant="button"
              onClick={() => {
                handleMaxReportLimit();
              }}
            >
              Create Custom Report
            </Link>
          </Box>
        ) : null}
      </AccessabilityToolbar>
      <Divider />
      <CardContainer theme={theme}>{renderOverviewCards()}</CardContainer>
      <CreateConfigModel isOpen={isOpen} setIsOpen={setIsOpen} />
    </>
  );
};
