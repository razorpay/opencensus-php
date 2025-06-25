import React, { Fragment, useEffect } from 'react';
import moment from 'moment';
import { ScheduleModalPropsType } from './types';
import {
  Button,
  CollapsibleForm,
  CollapsibleFormSection,
  Dropdown,
  DateTimeRangePicker,
  Switch,
  Text,
  TextInput,
  Badge,
  SelectInput,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  TimePickerField,
  Box,
  MultiSelectDropdown,
} from 'merchant_common/views/Reports/components';

import {
  ModalFooter,
  ReportModalHeader,
  ScrollableModalContent,
} from 'merchant_common/views/Reports/components/ReportModal/styled';
import { getDataDurations, getRepetitions, availableFormat } from './data';
import {
  CREATE_SCHEDULE_SUCCESS,
  CREATE_SCHEDULE_FAILED,
  EDIT_SCHEDULE_FAILED,
  EDIT_SCHEDULE_SUCCESS,
  MANDATORY_FIELD_REQUIRED,
} from 'merchant_common/views/Reports/constants/notifications';
import { createSchedule } from 'merchant_common/views/Reports/api/schedules';
import { getWhenScheduleDetails } from './utils';
import { useScheduleReportReducer } from './hooks/useScheduleReportReducer';
import { trackCreateEditScheduleModal } from 'merchant_common/views/Reports/configs/analytics.config';
import { ScheduleType } from 'merchant_common/views/Reports/types/schedule';
import { ModifierType } from 'merchant_common/views/Reports/components/types';
import { useReportsSplitzExperiments } from 'merchant_common/views/Reports/hooks';

export const ScheduleReportModal = ({
  allReportConfigs,
  params,
  closeModal,
  availableEmails,
  showNotification,
  startSchedulesPoll,
  stopSchedulesPoll,
  headers,
  generatedBy,
  parseSchedulePayloadBeforeSubmit,
  dashboardType,
}: ScheduleModalPropsType) => {
  const {
    isCustomEnabled,
    isSubmitButtonLoading,
    recipients,
    scheduleName,
    customDataDuration,
    selectedConfig,
    selectedDataDuration,
    selectedFormat,
    selectedRepetition,
    showErrorInSection,
    whenTime,
    setCustomDurationRange,
    setCustomEnabled,
    setRecipients,
    setScheduleName,
    setSelectedConfig,
    setSelectedDataDuration,
    setSelectedFormat,
    setSelectedRepetition,
    setShowErrorInSection,
    setSubmitButtonLoading,
    setWhenTime,
  } = useScheduleReportReducer(params?.scheduleData, allReportConfigs);

  const { isEdgeEnabled } = useReportsSplitzExperiments();

  const renderDurationInfo = () => {
    return !Boolean(customDataDuration?.startDate && whenTime?.date && selectedDataDuration?.label)
      ? 'Set date, time, duration and repetition'
      : `Starting from ${customDataDuration?.startDate
          .clone()
          .format('Do MMM YYYY')}, report delivery time ${whenTime?.date
          .clone()
          .format('h:mm a')}, weekly covering ${selectedDataDuration?.label.toLowerCase()} data`;
  };

  const validateDurationRange = (startDate, endDate) =>
    moment.isMoment(startDate) && moment.isMoment(endDate);

  const validateCustomDuration = () =>
    validateDurationRange(customDataDuration?.startDate, customDataDuration?.endDate);

  const actionViaModal = params?.scheduleData?.config_id ? 'Edit' : 'Create';

  const validationsForEachSections = [
    Boolean(selectedConfig && scheduleName),
    Boolean(
      customDataDuration &&
        selectedDataDuration?.value &&
        selectedRepetition?.value &&
        whenTime?.date,
    ),
    Boolean(recipients && Array.isArray(recipients) && recipients.length),
  ];

  const handleErrorStates = () => {
    const unfinishedSection = validationsForEachSections.findIndex(
      (sectionValidation) => !sectionValidation,
    );
    if (unfinishedSection < 0) return true;
    setShowErrorInSection(unfinishedSection);
    return false;
  };

  const handleSubmit = () => {
    trackCreateEditScheduleModal({
      actionName:
        actionViaModal === 'Create'
          ? 'Create Schedule Button Clicked'
          : 'Edit Schedule Button Clicked',
      dashboardType,
      properties: {
        modalType: actionViaModal,
      },
    });
    // validation
    if (handleErrorStates()) {
      if (actionViaModal === 'Edit' && !params?.scheduleData?.id) return null;

      const payload = {
        config_id: actionViaModal === 'Edit' ? undefined : selectedConfig!.id,
        name: scheduleName,
        period: selectedRepetition!.value,
        schedule_start_time: customDataDuration!.startDate
          .clone()
          .isBefore(moment().clone().add(1, 'day'), 'day')
          ? undefined
          : customDataDuration!.startDate.clone().unix(),
        schedule_end_time: customDataDuration!.endDate.clone().unix(),
        created_by: actionViaModal === 'Edit' ? undefined : generatedBy,
        template_overrides: Boolean(selectedFormat?.value)
          ? {
              file_meta: {
                extension: Boolean(selectedFormat?.value) ? selectedFormat?.value : undefined,
              },
            }
          : undefined,
        emails: recipients,
        ...getWhenScheduleDetails({
          selectedDataDuration,
          selectedRepetition,
          whenTime,
        }),
      };

      const parsedPayload = parseSchedulePayloadBeforeSubmit(payload as unknown as ScheduleType, {
        selectedConfig,
      });

      setSubmitButtonLoading(true);

      stopSchedulesPoll();

      createSchedule({
        headers,
        payload: parsedPayload,
        method: actionViaModal === 'Create' ? 'post' : 'patch',
        scheduleId: actionViaModal === 'Create' ? '' : params?.scheduleData?.id ?? '',
        isEdgeEnabled,
      })
        .then((data) => {
          if (!data.success || !data.data || !data.data.id) {
            trackCreateEditScheduleModal({
              actionName:
                actionViaModal === 'Create'
                  ? 'Schedule Creation Req Failed'
                  : 'Schedule Edit Req Failed',
              dashboardType,
              properties: {
                payload: payload as unknown as Record<string, unknown>,
                modalType: actionViaModal,
              },
            });
            return showNotification({
              type: 'error',
              message: actionViaModal === 'Create' ? CREATE_SCHEDULE_FAILED : EDIT_SCHEDULE_FAILED,
            });
          }

          trackCreateEditScheduleModal({
            actionName:
              actionViaModal === 'Create'
                ? 'Schedule Creation Req Success'
                : 'Schedule Edit Req Success',
            dashboardType,
            properties: {
              modalType: actionViaModal,
            },
          });

          showNotification({
            type: 'success',
            message: actionViaModal === 'Create' ? CREATE_SCHEDULE_SUCCESS : EDIT_SCHEDULE_SUCCESS,
          });
          return closeModal();
        })
        .catch(() => {
          trackCreateEditScheduleModal({
            actionName:
              actionViaModal === 'Create'
                ? 'Schedule Creation Req Failed'
                : 'Schedule Edit Req Failed',
            dashboardType,
            properties: {
              payload: payload as unknown as Record<string, unknown>,
              modalType: actionViaModal,
            },
          });

          return showNotification({
            type: 'error',
            message: actionViaModal === 'Create' ? CREATE_SCHEDULE_FAILED : EDIT_SCHEDULE_FAILED,
          });
        })
        .finally(() => {
          setSubmitButtonLoading(false);
          return startSchedulesPoll();
        });
    } else {
      showNotification({
        type: 'error',
        message: MANDATORY_FIELD_REQUIRED,
      });

      trackCreateEditScheduleModal({
        actionName:
          actionViaModal === 'Create'
            ? 'Schedule Create Validation Error'
            : 'Schedule Edit Validation Error',
        dashboardType,
        properties: {
          modalType: actionViaModal,
        },
      });
    }
    return null;
  };

  const validateCustomDurationForPicker = ({
    startDate,
    endDate,
  }: {
    startDate: moment.Moment;
    endDate: moment.Moment;
  }) => {
    switch (true) {
      case startDate.clone().diff(moment().clone(), 'days') >= 30:
        return {
          error: `*Schedule should start within 31 days (max: ${moment()
            .clone()
            .add(30, 'day')
            .format('DD MMM YYYY')}) from today.`,
        };
      case endDate.clone().diff(startDate, 'days') >= 185:
        return {
          error: `*You can only schedule report upto ${startDate
            .clone()
            .add(184, 'day')
            .format('D MMM, YYYY')}`,
        };

      default:
        return true;
    }
  };

  useEffect(() => {
    if (!params || !params.selectedConfig) return;
    const refConfig = allReportConfigs.find((data) => data.id === params.selectedConfig);
    setSelectedConfig(refConfig);
  }, [params]);

  useEffect(() => {
    trackCreateEditScheduleModal({
      actionName:
        actionViaModal === 'Create' ? 'Create Schedule Modal Opened' : 'Edit Schedule Modal Opened',
      dashboardType,
      properties: {
        modalType: actionViaModal,
      },
    });
  }, []);

  const dateRangeModifier: ModifierType = {
    DEFAULT_INFO:
      actionViaModal === 'Edit' &&
      params?.scheduleData?.schedule_start_time &&
      params?.scheduleData?.status != 'finished'
        ? `*This schedule started on ${moment
            .unix(params.scheduleData.schedule_start_time)
            .format(
              'MMM D, YYYY',
            )}. If you wish to modify, please note that the updated schedule can only run from ${moment()
            .clone()
            .add(1, 'day')
            .format('MMM D, YYYY')} onwards.`
        : `*Schedule can only run from ${moment()
            .clone()
            .add(1, 'day')
            .format('MMM D, YYYY')} onwards.`,
  };

  const dateRangeHelpText =
    params?.scheduleData?.status === 'finished' && Boolean(params?.scheduleData?.schedule_end_time)
      ? `This schedule ended on ${moment
          .unix(params!.scheduleData!.schedule_end_time)
          .format('MMM D, YYYY')}. Please choose a duration again.`
      : 'Choose a duration to schedule.';

  return (
    <Fragment>
      <ReportModalHeader>
        <Box display="flex" alignItems="center">
          <Text size="large">{actionViaModal} Report Schedule &nbsp;</Text>
          <Badge emphasis="intense" size="small" color="primary">
            New
          </Badge>
        </Box>
        <Text variant="body" size="medium" weight="regular" color="surface.text.gray.muted">
          You can create schedules on your reports and automate their delivery to your email. Choose
          what reports, where and how frequently you want them delivered.
        </Text>
      </ReportModalHeader>
      <ScrollableModalContent>
        <CollapsibleForm
          errorSectionIndex={showErrorInSection}
          validationsForEachSections={validationsForEachSections}
          disableSectionsExpandOnError={true}
          defaultOpen={0}
          style={{
            marginTop: 20,
          }}
        >
          <CollapsibleFormSection
            title="What report is this?"
            helpText={
              selectedConfig?.name || selectedFormat?.value
                ? `${selectedConfig?.name ?? 'Report type'}, ${scheduleName ?? 'schedule name'},${
                    selectedFormat?.value ?? 'format'
                  }`
                : 'Select the product, name the schedule and select a format.'
            }
          >
            <Dropdown selectionType="single">
              <SelectInput
                necessityIndicator="required"
                label="Select Report"
                onChange={({ values }) => setSelectedConfig(allReportConfigs[+values[0]])}
                placeholder="Select A Report"
                validationState={
                  showErrorInSection === 0 ? (Boolean(selectedConfig) ? 'none' : 'error') : 'none'
                }
                helpText={
                  selectedConfig?.description ?? 'Select report you want to receive report about.'
                }
                errorText="Mandatory Field: Select report you want to receive report about."
                value={allReportConfigs
                  .findIndex((config) => selectedConfig && config.id === selectedConfig.id)
                  .toString()}
                isDisabled={actionViaModal === 'Edit'}
              />
              <DropdownOverlay>
                <ActionList
                  options={allReportConfigs}
                  itemComponent={({ data: { id, name }, index }) => (
                    <ActionListItem key={id} title={name} value={index.toString()} />
                  )}
                />
              </DropdownOverlay>
            </Dropdown>

            <TextInput
              label="Schedule Name"
              placeholder="Eg: Schedule At 5pm daily"
              value={scheduleName}
              onChange={({ value }) => setScheduleName(value ?? '')}
              helpText="Enter a schedule name."
              errorText="Mandatory Field: Enter a schedule name."
              necessityIndicator="required"
              validationState={
                showErrorInSection === 0 ? (scheduleName?.length ? 'none' : 'error') : 'none'
              }
            />

            <Dropdown selectionType="single">
              <SelectInput
                label="Select Format"
                name="selectedConfig"
                onChange={({ values }) => setSelectedFormat(availableFormat[+values[0]])}
                placeholder="Excel or CSV"
                validationState="none"
                helpText="Select the format in which you want to receive the report in."
                necessityIndicator="optional"
                value={availableFormat
                  .findIndex((format) => format.value === selectedFormat?.value)
                  .toString()}
              />
              <DropdownOverlay>
                <ActionList
                  options={availableFormat}
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
          </CollapsibleFormSection>
          <CollapsibleFormSection
            title="What will you receive in this report?"
            helpText={renderDurationInfo() ?? 'Set date, time, duration and repetition'}
          >
            <Box position="relative">
              <DateTimeRangePicker
                label="Choose Schedule Duration"
                helpText={dateRangeHelpText}
                errorText={`Mandatory Field: ${dateRangeHelpText}`}
                placeHolder="Select a duration to schedule."
                onChange={setCustomDurationRange}
                value={customDataDuration}
                disableFuture={false}
                minDate={moment().clone().add(1, 'day').startOf('day')}
                disableTimeSelection
                necessityIndicator="required"
                validateRange={validateCustomDurationForPicker}
                validationState={showErrorInSection === 1 ? validateCustomDuration() : true}
                modifiers={dateRangeModifier}
              />
            </Box>

            <>
              <Box alignItems="center" justifyContent="space-between" display="flex">
                <Text variant="body" size="small" weight="semibold" color="surface.text.gray.muted">
                  Data & Repetition
                </Text>
                <Switch
                  label="Custom"
                  value={isCustomEnabled}
                  onChange={(bool) => {
                    setCustomEnabled(bool);
                    trackCreateEditScheduleModal({
                      actionName: 'Custom Repetition Toggled',
                      properties: {
                        is_custom_repetition: bool,
                        modalType: actionViaModal,
                      },
                      dashboardType,
                    });
                  }}
                />
              </Box>
              <br />
              <Dropdown selectionType="single">
                <SelectInput
                  label="Data Duration"
                  necessityIndicator="required"
                  helpText="Select data duration to cover in report."
                  placeholder="Data duration covered in each report"
                  onChange={({ values }) =>
                    setSelectedDataDuration(getDataDurations(isCustomEnabled)[+values[0]])
                  }
                  validationState={
                    showErrorInSection === 1
                      ? selectedDataDuration?.value
                        ? 'none'
                        : 'error'
                      : 'none'
                  }
                  errorText="Mandatory Field: Select duration to cover in report."
                  value={getDataDurations(isCustomEnabled)
                    .findIndex(
                      (refDataDuration) => refDataDuration.value === selectedDataDuration?.value,
                    )
                    .toString()}
                />
                <DropdownOverlay>
                  <ActionList
                    options={getDataDurations(isCustomEnabled)}
                    itemComponent={({ data: { label }, index }) => (
                      <ActionListItem key={label} title={label} value={index.toString()} />
                    )}
                  />
                </DropdownOverlay>
              </Dropdown>

              <br />

              <Dropdown selectionType="single">
                <SelectInput
                  label="Repetition"
                  helpText="Select repetition frequency."
                  errorText="Mandatory Field: Select repetition frequency."
                  placeholder="None"
                  necessityIndicator="required"
                  isDisabled={!selectedDataDuration?.value}
                  onChange={({ values }) =>
                    setSelectedRepetition(
                      (selectedDataDuration
                        ? getRepetitions(selectedDataDuration.value, isCustomEnabled)
                        : [])[+values[0]],
                    )
                  }
                  validationState={
                    showErrorInSection === 1
                      ? selectedRepetition?.value
                        ? 'none'
                        : 'error'
                      : 'none'
                  }
                  value={(selectedDataDuration
                    ? getRepetitions(selectedDataDuration.value, isCustomEnabled)
                    : []
                  )
                    .findIndex((refRepetition) => refRepetition.label === selectedRepetition?.label)
                    .toString()}
                />
                <DropdownOverlay>
                  <ActionList
                    options={
                      selectedDataDuration
                        ? getRepetitions(selectedDataDuration.value, isCustomEnabled)
                        : []
                    }
                    itemComponent={({ data: { label }, index }) => (
                      <ActionListItem key={label} title={label} value={index.toString()} />
                    )}
                  />
                </DropdownOverlay>
              </Dropdown>

              <br />

              <TimePickerField
                label="Set Time"
                helpText="Select the time to include in the schedule."
                errorText="Mandatory Field: Select the time to include in the schedule."
                onChange={setWhenTime}
                defaultValue={whenTime?.date ?? moment().clone()}
                necessityIndicator="required"
                minutesInterval={15}
                validate={() => (showErrorInSection === 1 ? moment.isMoment(whenTime?.date) : true)}
              />
            </>
          </CollapsibleFormSection>
          <CollapsibleFormSection
            title="Who will receive this report?"
            helpText={
              validationsForEachSections[2]
                ? recipients.join(', ')
                : "Add receiver's email addresses."
            }
          >
            <Dropdown selectionType="multiple">
              <SelectInput
                label="Add Recipient's Details"
                helpText="Select report you want to receive report about."
                errorText="Mandatory Field: Select report you want to receive report about."
                placeholder="Start typing emails to add"
                value={recipients}
                onChange={({ values }) => setRecipients(values)}
                validationState={
                  showErrorInSection === 2
                    ? validationsForEachSections[2]
                      ? 'none'
                      : 'error'
                    : 'none'
                }
                necessityIndicator="required"
                accessibilityLabel="Add Recipient Field"
                testID="add_recipient_field"
                isRequired
              />
              <DropdownOverlay>
                <ActionList
                  options={availableEmails && Array.isArray(availableEmails) ? availableEmails : []}
                  itemComponent={({ data, index }) => (
                    <ActionListItem
                      key={`${data}-${index}`}
                      title={data}
                      value={data}
                      testID={data}
                    />
                  )}
                />
              </DropdownOverlay>
            </Dropdown>
          </CollapsibleFormSection>
        </CollapsibleForm>
        <ModalFooter>
          <Button
            isDisabled={isSubmitButtonLoading}
            size="medium"
            onClick={closeModal}
            variant="tertiary"
            marginRight={'spacing.4'}
          >
            Cancel
          </Button>

          <Button
            testID="create_schedule"
            isLoading={isSubmitButtonLoading}
            size="medium"
            onClick={handleSubmit}
            variant="primary"
            accessibilityLabel={`${actionViaModal} Schedule`}
          >
            {actionViaModal} Schedule
          </Button>
        </ModalFooter>
      </ScrollableModalContent>
    </Fragment>
  );
};
