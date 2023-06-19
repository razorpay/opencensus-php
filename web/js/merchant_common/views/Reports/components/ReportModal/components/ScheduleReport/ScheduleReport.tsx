import React, { Fragment, useEffect } from 'react';
import moment from 'moment';
import { ScheduleModalPropsType } from './types';
import {
  Button,
  CollapsibleForm,
  CollapsibleFormSection,
  Dropdown,
  Heading,
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
import { patchedSelectOnChange } from 'merchant_common/views/Reports/components/blade.patch';
import { trackCreateEditScheduleModal } from 'merchant_common/views/Reports/configs/analytics.config';
import { ScheduleType } from 'merchant_common/views/Reports/types/schedule';

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
    saveReportAs,
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
    setSaveReportAs,
    setScheduleName,
    setSelectedConfig,
    setSelectedDataDuration,
    setSelectedFormat,
    setSelectedRepetition,
    setShowErrorInSection,
    setSubmitButtonLoading,
    setWhenTime,
  } = useScheduleReportReducer(params?.scheduleData, allReportConfigs);

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
        schedule_start_time: customDataDuration!.startDate.isBefore(moment(), 'day')
          ? undefined
          : customDataDuration!.startDate.clone().unix(),
        schedule_end_time: customDataDuration!.endDate.clone().unix(),
        created_by: actionViaModal === 'Edit' ? undefined : generatedBy,
        template_overrides:
          Boolean(selectedFormat?.value) || Boolean(saveReportAs)
            ? {
                file_meta: {
                  extension: Boolean(selectedFormat?.value) ? selectedFormat?.value : undefined,
                  filename: Boolean(saveReportAs) ? saveReportAs : undefined,
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
      case startDate.diff(moment(), 'days') >= 30:
        return {
          error: `Schedule should start within 30 days (max: ${moment()
            .add(30, 'day')
            .format('DD MMM YYYY')}) from today.`,
        };
      case endDate.diff(startDate, 'days') >= 184:
        return {
          error: `You can schedule upto ${startDate
            .clone()
            .add(184, 'day')
            .format('DD MMM YYYY')}.`,
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

  return (
    <Fragment>
      <ReportModalHeader>
        <Box display="flex" alignItems="center">
          <Heading variant="regular">{actionViaModal} Report Schedule &nbsp;</Heading>
          <Badge contrast="high" size="small" variant="blue">
            New
          </Badge>
        </Box>
        <Text
          variant="body"
          size="medium"
          weight="regular"
          color="surface.text.subdued.lowContrast"
        >
          You can now automate receiving your product's reports to your email by scheduling them.
          Choose what reports, where and how frequently you want them delivered with a report
          schedule.
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
                : 'Select the product, name the report, and the schedule name.'
            }
          >
            <Dropdown selectionType="single">
              <SelectInput
                necessityIndicator="required"
                label="Select Report"
                onChange={patchedSelectOnChange(({ values }) =>
                  setSelectedConfig(allReportConfigs[+values[0]]),
                )}
                placeholder="Select A Report"
                validationState={
                  showErrorInSection === 0 ? (Boolean(selectedConfig) ? 'none' : 'error') : 'none'
                }
                helpText={
                  selectedConfig?.description ?? 'Select report you want to receive report about.'
                }
                errorText="Mandatory Field: Select report you want to receive report about."
              />
              <DropdownOverlay>
                {/* @blade-patch -> "key" */}
                <ActionList key={selectedConfig?.id} surfaceLevel={2}>
                  {allReportConfigs.map((data, index) => {
                    return (
                      <ActionListItem
                        key={data.id}
                        isDefaultSelected={
                          selectedConfig && allReportConfigs[index].id === selectedConfig.id
                        }
                        title={data.name}
                        value={index.toString()}
                      />
                    );
                  })}
                </ActionList>
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
            />

            <TextInput
              label="Save Report As"
              placeholder="Eg: Monthly Recon Report"
              value={saveReportAs}
              onChange={({ value }) => setSaveReportAs(value ?? '')}
              helpText="Enter file name for your report."
              necessityIndicator="optional"
            />

            <Dropdown selectionType="single">
              <SelectInput
                label="Select Format"
                name="selectedConfig"
                onChange={patchedSelectOnChange(({ values }) =>
                  setSelectedFormat(availableFormat[+values[0]]),
                )}
                placeholder="Excel or CSV"
                validationState="none"
                helpText="Select the format in which you want to receive the report in."
                necessityIndicator="optional"
              />
              <DropdownOverlay>
                <ActionList surfaceLevel={2}>
                  {availableFormat.map(({ label, value }, index) => {
                    return (
                      <ActionListItem
                        key={value}
                        title={label}
                        isDefaultSelected={value === selectedFormat?.value}
                        value={index.toString()}
                        testID={label}
                      />
                    );
                  })}
                </ActionList>
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
                helpText="Choose a duration to schedule."
                errorText="Mandatory Field: Choose a duration to schedule."
                placeHolder="Select a duration to schedule."
                onChange={setCustomDurationRange}
                value={customDataDuration}
                disableFuture={false}
                disablePast
                necessityIndicator="required"
                validateRange={validateCustomDurationForPicker}
                validationState={showErrorInSection === 1 ? validateCustomDuration() : true}
                modifiers={{
                  INFO_WHEN_PAST_DISABLED: `*Schedule can only run from ${moment().format(
                    'MMM d, YYYY',
                  )} onwards. While data covered can be past data. By default schedule will run infinitely until deleted.`,
                }}
              />
            </Box>

            <>
              <Box alignItems="center" justifyContent="space-between" display="flex">
                <Text variant="body" type="subdued" size="small" weight="bold">
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
                  onChange={patchedSelectOnChange(({ values }) =>
                    setSelectedDataDuration(getDataDurations(isCustomEnabled)[+values[0]]),
                  )}
                  validationState={
                    showErrorInSection === 1
                      ? selectedDataDuration?.value
                        ? 'none'
                        : 'error'
                      : 'none'
                  }
                  errorText="Mandatory Field: Select duration to cover in report."
                />
                <DropdownOverlay>
                  <ActionList key={selectedDataDuration?.value} surfaceLevel={2}>
                    {getDataDurations(isCustomEnabled).map((data, index) => (
                      <ActionListItem
                        key={data.label}
                        title={data.label}
                        value={index.toString()}
                        isDefaultSelected={data?.value === selectedDataDuration?.value}
                      />
                    ))}
                  </ActionList>
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
                  onChange={patchedSelectOnChange(({ values }) =>
                    setSelectedRepetition(
                      (selectedDataDuration
                        ? getRepetitions(selectedDataDuration.value, isCustomEnabled)
                        : [])[+values[0]],
                    ),
                  )}
                  validationState={
                    showErrorInSection === 1
                      ? selectedRepetition?.value
                        ? 'none'
                        : 'error'
                      : 'none'
                  }
                />
                <DropdownOverlay>
                  <ActionList
                    key={`${selectedDataDuration?.value}${selectedRepetition?.value}${
                      isCustomEnabled ? '1' : '0'
                    }`}
                    surfaceLevel={2}
                  >
                    {(selectedDataDuration
                      ? getRepetitions(selectedDataDuration.value, isCustomEnabled)
                      : []
                    ).map((data, index) => (
                      <ActionListItem
                        key={data.label}
                        title={data.label}
                        value={index.toString()}
                        isDefaultSelected={
                          selectedRepetition
                            ? data.label === selectedRepetition?.label
                            : index === 0
                        }
                      />
                    ))}
                  </ActionList>
                </DropdownOverlay>
              </Dropdown>

              <br />

              <TimePickerField
                label="Set Time"
                helpText="Select the time to include in the schedule."
                errorText="Mandatory Field: Select the time to include in the schedule."
                onChange={setWhenTime}
                defaultValue={whenTime?.date ?? moment()}
                necessityIndicator="required"
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
            <MultiSelectDropdown
              label="Add Recipient's Details"
              helpText="Select report you want to receive report about."
              errorText="Mandatory Field: Select report you want to receive report about."
              placeHolder="Start typing emails to add"
              value={recipients}
              onChange={setRecipients}
              shouldCloseDropdownOnSelect={false}
              validate={() => (showErrorInSection === 2 ? validationsForEachSections[2] : true)}
              isSearchable
              options={availableEmails && Array.isArray(availableEmails) ? availableEmails : []}
              isVirtualized
              itemHeight={36}
              ariaLabelBy="Add Recipient Field"
              necessityIndicator="required"
            />
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
