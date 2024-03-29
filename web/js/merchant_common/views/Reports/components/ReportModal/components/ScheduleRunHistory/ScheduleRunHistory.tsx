import React from 'react';
import {
  Button,
  PlusIcon,
  ReportModal,
  Dropdown,
  Text,
  SelectInput,
  DropdownOverlay,
  ActionList,
  ActionListItem,
} from 'merchant_common/views/Reports/components';
import {
  ControlPanel,
  DropdownWrapper,
  ControlPanelRight,
  ModalScrollableTable,
  RepeatOnWrapper,
  ActionButtonWrapper,
} from './styled';
import { SchedulesPropsType } from './types';
import { connect } from 'react-redux';
import { openModal } from 'merchant_common/reducers/modals';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import { ReportModalHeader } from 'merchant_common/views/Reports/components/ReportModal/styled';
import { FlexCentered } from 'merchant_common/views/Reports/components/styled';
import { RunHistoryTable } from './components/RunHistoryTable';
import { useRunHistoryReducer } from './hooks/useRunHistoryReducer';
import { downloadsFilterDropdown } from 'merchant_common/views/Reports/features/Downloads/constants/dropdownOptions';

const mapStateToProps = ({ reportsCore }, { dashboardType }) => {
  const { allConfigs } = reportsCore[dashboardType].overview.reportConfigs;
  return {
    isAllConfigLoaded: !allConfigs.loading && !allConfigs.error,
  };
};

const mapDispatchToProps = (dispatch) => ({
  openModal: (modal) => dispatch(openModal(modal)),
});

const ScheduleRunHistory = connect(
  mapStateToProps,
  mapDispatchToProps,
)(
  ({
    isAllConfigLoaded,
    dashboardType,
    openModal,
    params: { scheduleData },
  }: SchedulesPropsType): JSX.Element => {
    const { theme } = useTheme();
    const logsHistoryReducer = useRunHistoryReducer(dashboardType);

    const handleEditSchedule = () => {
      openModal({
        component: (
          <ReportModal
            type="create_edit_schedule"
            dashboardType={dashboardType}
            params={{
              scheduleData,
            }}
          />
        ),
        size: '',
      });
    };

    return (
      <>
        <ReportModalHeader>
          <Text size="large">History of Report Run</Text>
          <Text variant="body" size="medium" weight="regular" color="surface.text.gray.muted">
            Revisit the reports you have received in this schedule
          </Text>
        </ReportModalHeader>
        <ControlPanel>
          <RepeatOnWrapper>
            <FlexCentered>
              <Text variant="body" size="medium" weight="regular" color="surface.text.gray.muted">
                Repeat on:
              </Text>
              &nbsp; &nbsp;
              <Text variant="body" size="medium" weight="semibold" color="surface.text.gray.muted">
                {scheduleData.period[0].toUpperCase() + scheduleData.period.slice(1)}
              </Text>
            </FlexCentered>
          </RepeatOnWrapper>

          <ControlPanelRight>
            <ActionButtonWrapper>
              <Button
                onClick={handleEditSchedule}
                variant="primary"
                icon={PlusIcon}
                iconPosition="left"
                isFullWidth
                accessibilityLabel="Edit Schedule Button"
                isLoading={!isAllConfigLoaded}
              >
                Edit Schedule
              </Button>
            </ActionButtonWrapper>
            <DropdownWrapper>
              <Dropdown selectionType="single">
                <SelectInput
                  label=""
                  onChange={({ values }) =>
                    logsHistoryReducer.handleLogsFilter(downloadsFilterDropdown[+values[0]])
                  }
                  placeholder="Choose Logs Filter"
                  validationState="none"
                  value={downloadsFilterDropdown
                    .findIndex((refFilter) => refFilter.value === logsHistoryReducer?.filter?.value)
                    .toString()}
                />
                <DropdownOverlay key={logsHistoryReducer?.filter.value}>
                  <ActionList
                    options={downloadsFilterDropdown}
                    itemComponent={({ data: { label }, index }) => (
                      <ActionListItem
                        key={label}
                        title={label}
                        value={index.toString()}
                        testID={label}
                      />
                    )}
                  />
                </DropdownOverlay>
              </Dropdown>
            </DropdownWrapper>
          </ControlPanelRight>
        </ControlPanel>
        <ModalScrollableTable theme={theme} heightOffset={92.5} initialWidth={1100}>
          <RunHistoryTable
            dashboardType={dashboardType}
            scheduleId={scheduleData.id}
            logsHistoryReducer={logsHistoryReducer}
          />
        </ModalScrollableTable>
      </>
    );
  },
);

export default ScheduleRunHistory;
