import React, { useEffect } from 'react';
import {
  Dropdown,
  SelectInput,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  ReportModal,
  Button,
  FileTextIcon,
} from 'merchant_common/views/Reports/components';

import { ControlPanel, SchedulesWrapper, DropdownWrapper } from './styled';
import { SchedulesPropsType } from './types';
import { SchedulesTable } from './components/SchedulesTable';
import { connect } from 'react-redux';
import { handleScheduleFilter } from 'merchant_common/views/Reports/redux/reducer';
import { openModal } from 'merchant_common/reducers/modals';
import { useDashboardType } from 'merchant_common/views/Reports/contexts/ReportsContext';
import { schedulesFilterDropdown } from './data/dropdownOptions';
import { trackScheduleSection } from 'merchant_common/views/Reports/configs/analytics.config';

const mapStateToProps = ({ reportsCore }, { dashboardType }) => {
  const { allConfigs } = reportsCore[dashboardType].overview.reportConfigs;
  const { filter } = reportsCore[dashboardType].schedules;

  return {
    isAllConfigLoaded: !allConfigs.loading && !allConfigs.error,
    scheduleFilter: filter,
  };
};

const mapDispatchToProps = (dispatch, { dashboardType }) => ({
  openModal: (modal) => dispatch(openModal(modal)),
  handleScheduleFilter: (payload) =>
    dispatch(handleScheduleFilter({ filter: payload, dashboardType })),
});

const SchedulesSection = connect(
  mapStateToProps,
  mapDispatchToProps,
)(
  ({
    handleScheduleFilter,
    scheduleFilter,
    dashboardType,
    isAllConfigLoaded,
    openModal,
  }: SchedulesPropsType): JSX.Element => {
    useEffect(() => {
      if (isAllConfigLoaded) {
        trackScheduleSection({ actionName: 'Loaded', dashboardType });
      }
    }, [isAllConfigLoaded]);

    const handleSchedulesFilterChange = (refFilter) => {
      if (!refFilter) return;
      trackScheduleSection({
        actionName: 'Schedules Filter Interaction',
        properties: {
          interaction_type: 'select',
          selectedFilter: refFilter?.label,
        },
        dashboardType,
      });
      handleScheduleFilter(refFilter?.value);
    };

    const handleScheduleClick = () => {
      openModal({
        component: <ReportModal type="create_edit_schedule" dashboardType={dashboardType} />,
        size: '',
      });
    };

    return (
      <SchedulesWrapper>
        <ControlPanel>
          <Button
            onClick={handleScheduleClick}
            variant="primary"
            icon={FileTextIcon}
            iconPosition="left"
            accessibilityLabel="Create Schedule Button"
            isLoading={!isAllConfigLoaded}
          >
            Create Schedule
          </Button>
          <DropdownWrapper>
            <Dropdown selectionType="single">
              <SelectInput
                label=""
                onChange={({ values }) =>
                  handleSchedulesFilterChange(schedulesFilterDropdown[+values[0]])
                }
                placeholder="Choose Schedules Filter"
                validationState="none"
                value={schedulesFilterDropdown
                  .findIndex((refFilter) => refFilter.value === scheduleFilter)
                  .toString()}
              />
              <DropdownOverlay>
                <ActionList
                  options={schedulesFilterDropdown}
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
        <SchedulesTable />
      </SchedulesWrapper>
    );
  },
);

export const Schedules = (props) => {
  const dashboardType = useDashboardType();
  return <SchedulesSection dashboardType={dashboardType} {...props} />;
};
