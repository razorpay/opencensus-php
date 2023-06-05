import React, { useEffect } from 'react';
import {
  Button,
  PlusIcon,
  ReportModal,
  Dropdown,
  SelectInput,
  DropdownOverlay,
  ActionList,
  ActionListItem,
} from 'merchant_common/views/Reports/components';
import { ControlPanel, DownloadsWrapper, DropdownWrapper } from './style';
import { DownloadsPropsType } from './types';
import { DownloadsTable } from './components/DownloadsTable';
import { connect } from 'react-redux';
import { downloadsFilterDropdown } from 'merchant_common/views/Reports/features/Downloads/constants/dropdownOptions';
import { handleLogsFilter } from 'merchant_common/views/Reports/redux/reducer';
import { openModal } from 'merchant_common/reducers/modals';
import { useDashboardType } from 'merchant_common/views/Reports/contexts/ReportsContext';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import { trackDownloadsSection } from 'merchant_common/views/Reports/configs/analytics.config';
import { patchedSelectOnChange } from 'merchant_common/views/Reports/components/blade.patch';

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
            type={'download_report'}
            dashboardType={dashboardType}
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
      <DownloadsWrapper theme={theme}>
        <ControlPanel theme={theme}>
          <Button
            onClick={handleDownloadReportClick}
            variant="primary"
            icon={PlusIcon}
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
                onChange={patchedSelectOnChange(({ values }) =>
                  handleDownloadsFilter(downloadsFilterDropdown[+values[0]]),
                )}
                placeholder="Choose Logs Filter"
                validationState="none"
              />
              <DropdownOverlay>
                <ActionList surfaceLevel={2}>
                  {downloadsFilterDropdown.map(({ label, value }, index) => (
                    <ActionListItem
                      key={index}
                      isDefaultSelected={value === logTableFilterType}
                      title={label}
                      value={index.toString()}
                      testID={label}
                    />
                  ))}
                </ActionList>
              </DropdownOverlay>
            </Dropdown>
          </DropdownWrapper>
        </ControlPanel>
        <DownloadsTable />
      </DownloadsWrapper>
    );
  },
);

export const Downloads = (props): JSX.Element => {
  const dashboardType = useDashboardType();

  return <DownloadsSection dashboardType={dashboardType} {...props} />;
};
