import React, { useEffect } from 'react';
import { Box, Heading } from '@razorpay/blade/components';
import { DownloadsPropsType } from 'merchant_common/views/Reports/features/Downloads/types';
import { connect } from 'react-redux';

import Wrapper from 'merchant/views/GCMS/shared/Wrapper';
import { openModal } from 'merchant_common/reducers/modals';
import {
  Button,
  ReportModal,
  Dropdown,
  SelectInput,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  DownloadIcon,
} from 'merchant_common/views/Reports/components';
import { trackDownloadsSection } from 'merchant_common/views/Reports/configs/analytics.config';
import { useDashboardType } from 'merchant_common/views/Reports/contexts/ReportsContext';
import { downloadsFilterDropdown } from 'merchant_common/views/Reports/features/Downloads/constants/dropdownOptions';
import {
  ControlPanel,
  DropdownWrapper,
} from 'merchant_common/views/Reports/features/Downloads/style';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import { handleLogsFilter } from 'merchant_common/views/Reports/redux/reducer';

import { DownloadsTable } from './components/DownloadsTable';
const mapStateToProps = ({ reportsCore }, { dashboardType }) => {
  const { allConfigs } = reportsCore[dashboardType].overview.reportConfigs;
  const { filter } = reportsCore[dashboardType].downloads;

  return {
    isAllConfigLoaded: !allConfigs.loading && !allConfigs.error,
    logTableFilterType: filter,
  };
};

const mapDispatchToProps = (dispatch, { dashboardType }) => ({
  openModal: (modal) => dispatch(openModal(modal)),
  handleLogsTableFilterChange: (payload) =>
    dispatch(handleLogsFilter({ filter: payload, dashboardType })),
});

const DownloadsSection = connect(
  mapStateToProps,
  mapDispatchToProps,
)(
  ({
    isAllConfigLoaded,
    dashboardType,
    logTableFilterType,
    openModal,
    handleLogsTableFilterChange,
    headers,
  }: DownloadsPropsType): JSX.Element => {
    const { theme } = useTheme();

    const handleDownloadReportClick = () => {
      trackDownloadsSection({ actionName: 'Download Report Button Click', dashboardType });
      openModal({
        component: (
          <ReportModal
            params={{
              startPollOnSubmit: false,
            }}
            type="download_report"
            dashboardType={dashboardType}
            headers={headers}
          />
        ),
        size: 'custom',
      });
    };

    useEffect(() => {
      if (isAllConfigLoaded) {
        trackDownloadsSection({ actionName: 'Downloads Section Loaded', dashboardType });
      }
    }, [isAllConfigLoaded]);

    const handleDownloadsFilter = (refFilter) => {
      if (!refFilter) return;
      trackDownloadsSection({
        actionName: 'Download Filter Interaction',
        properties: {
          interaction_type: 'select',
          selectedFilter: refFilter?.label,
        },
        dashboardType,
      });
      handleLogsTableFilterChange(refFilter?.value);
    };

    return (
      <Wrapper>
        <div className="tabbed-container">
          <Box marginBottom="spacing.5">
            <Heading color="surface.text.gray.subtle" size="large">
              Reports
            </Heading>
          </Box>
          <div className="content">
            <ControlPanel theme={theme}>
              <Button
                onClick={handleDownloadReportClick}
                variant="primary"
                icon={DownloadIcon}
                iconPosition="left"
                accessibilityLabel="Download Report Button"
                isLoading={!isAllConfigLoaded}
              >
                Download Report
              </Button>
              <DropdownWrapper>
                <Dropdown selectionType="single">
                  <SelectInput
                    label=""
                    onChange={({ values }) =>
                      handleDownloadsFilter(downloadsFilterDropdown[+values[0]])
                    }
                    placeholder="Choose Logs Filter"
                    validationState="none"
                    value={downloadsFilterDropdown
                      .findIndex((refFilter) => refFilter.value === logTableFilterType)
                      .toString()}
                  />
                  <DropdownOverlay>
                    <ActionList
                      options={downloadsFilterDropdown}
                      itemComponent={({ data: { label }, index }) => (
                        <ActionListItem
                          key={index}
                          title={label}
                          value={index.toString()}
                          testID={label}
                        />
                      )}
                    />
                  </DropdownOverlay>
                </Dropdown>
              </DropdownWrapper>
            </ControlPanel>
            <DownloadsTable headers={headers} />
          </div>
        </div>
      </Wrapper>
    );
  },
);

export const Downloads = (props): JSX.Element => {
  const dashboardType = useDashboardType();

  return <DownloadsSection dashboardType={dashboardType} {...props} />;
};
