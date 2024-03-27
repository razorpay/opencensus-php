import React, { useState } from 'react';
import {
  ActionList,
  ActionListItem,
  Box,
  Button,
  Dropdown,
  DropdownOverlay,
  Heading,
  SelectInput,
  Text,
} from '@razorpay/blade/components';
import moment, { unitOfTime } from 'moment';

import { merchantFetch } from 'merchant/utils/ajax';
import {
  CloseIcon,
  IconButton,
  Suspense,
  DateTimeRangePicker,
} from 'merchant_common/views/Reports/components';
import {
  ReportCloseButton,
  ReportModalHeader,
  ModalFooter,
} from 'merchant_common/views/Reports/components/ReportModal/styled';

import {
  REPORT_MODAL_CONTENT,
  REPORTS_PRESETS,
  CONFIG_IDS,
  REPORT_POST_INVALID_RES,
  REPORT_POST_SUCCESS,
} from './constants';
import { ReportModalWrapper } from './styled';
import { ReportModalProps, SelectedRangeType } from './types';
import { getDurationCoveredInReports, getInitialState } from './utils';
import { trackEvent } from '../../common/trackEvents';

const ReportModal: React.FC<ReportModalProps> = (props) => {
  const { entity, availableEmails, generatedBy, onCloseCallback, showNotification } = props;
  const { title, filename } = REPORT_MODAL_CONTENT[entity];

  const [dateRange, setDateRange] = useState(() => getInitialState());
  const [isCustomDurationEnabled, setCustomDuration] = useState(false);
  const [customDurationRange, setCustomDurationRange] = useState<SelectedRangeType>();
  const [recipients, setRecipients] = useState([]);
  const [isSubmitButtonLoading, setSubmitButtonLoading] = useState(false);

  const handleClose = () => {
    trackEvent({
      objectName: 'Download list - Close',
      properties: { section: entity },
    });
    onCloseCallback();
  };

  const handlePreset = ({ values }) => {
    const selectedOption = REPORTS_PRESETS.find((preset) => preset.value === values[0]);
    if (!selectedOption) return;

    if (selectedOption.value === 'custom') {
      setCustomDuration(true);
      setDateRange({ startDate: null, endDate: null, preset: selectedOption });
      return;
    }

    const { duration, unit } = selectedOption;
    const currentDate = moment().subtract(1, 'day');
    const endDate = moment(currentDate).startOf('day');
    const startDate = endDate
      .clone()
      .subtract(duration, unit as unitOfTime.DurationConstructor)
      .startOf('day');

    const newDateRange = {
      startDate: startDate.unix(),
      endDate: endDate.unix(),
      preset: selectedOption,
    };

    trackEvent({
      objectName: 'Download list - Preset Duration',
      actionName: 'Change',
      properties: { section: entity, dateRange: newDateRange },
    });

    setDateRange(newDateRange);
  };

  const handleCustomRange = (newDateRange: SelectedRangeType) => {
    trackEvent({
      objectName: 'Download list - Custom Duration',
      actionName: 'Change',
      properties: { section: entity, dateRange: newDateRange },
    });
    setCustomDurationRange(newDateRange);
  };

  const handleRecipients = ({ values }) => setRecipients(values);

  const validateCustomDurationForPicker = ({ startDate, endDate }) => {
    switch (true) {
      case endDate.diff(startDate, 'day') > 90:
        return { error: 'Max allowed range is 90 days.' };
      case Boolean(endDate.diff(startDate, 'day') > 90):
        return { error: 'Max allowed range is 31 days for aggregated partner reports.' };
      default:
        return true;
    }
  };

  const handleSubmit = async () => {
    const payload = {
      generated_by: generatedBy,
      config_id: CONFIG_IDS[entity],
      emails: recipients?.length ? recipients : undefined,
      template_overrides: { file_meta: { extension: 'xlsx', filename: `${filename}-report` } },
      ...getDurationCoveredInReports({ isCustomDurationEnabled, customDurationRange, dateRange }),
    };
    setSubmitButtonLoading(true);
    try {
      await merchantFetch({ url: 'reporting/logs', method: 'POST', data: payload });
      showNotification({ type: 'success', message: REPORT_POST_SUCCESS });
      trackEvent({
        objectName: 'Download list - Response',
        actionName: 'success',
        properties: { section: entity, payload },
      });
      handleClose();
    } catch (error: any) {
      trackEvent({
        objectName: 'Download list - Response',
        actionName: 'error',
        properties: {
          section: entity,
          error_code: error?.status_code,
          error_description: error?.errors[0],
        },
      });
      showNotification({ type: 'error', message: REPORT_POST_INVALID_RES });
    } finally {
      setSubmitButtonLoading(false);
    }
  };

  const { preset } = dateRange;

  return (
    <div aria-label="risk-analytics-report-modal">
      <ReportCloseButton>
        <IconButton
          accessibilityLabel="close-modal"
          size="large"
          contrast="low"
          onClick={handleClose}
          icon={CloseIcon}
        />
      </ReportCloseButton>
      <Suspense minWidth={750}>
        <ReportModalWrapper width={625} height="auto">
          <ReportModalHeader>
            <Heading variant="regular">{title}</Heading>
            <Text
              variant="body"
              size="medium"
              weight="regular"
              color="surface.text.subdued.lowContrast"
            >
              The file would be sent within 10 minutes of initiating request
            </Text>
          </ReportModalHeader>
          <Box
            display="flex"
            flexDirection="column"
            gap="spacing.4"
            paddingTop="spacing.4"
            paddingBottom="spacing.11"
          >
            {isCustomDurationEnabled ? (
              <DateTimeRangePicker
                label="Select Duration"
                helpText="Choose a duration to cover in report."
                placeHolder="Select duration"
                onChange={handleCustomRange}
                value={customDurationRange}
                minDate={moment().subtract(90, 'days')}
                maxDate={moment().subtract(1, 'day')}
                validateRange={validateCustomDurationForPicker}
                necessityIndicator="required"
                disableTimeSelection
              />
            ) : (
              <Dropdown selectionType="single">
                <SelectInput
                  label="Select Duration"
                  helpText="Select duration to cover in report."
                  placeholder="Select duration"
                  necessityIndicator="required"
                  value={preset.value}
                  onChange={handlePreset}
                />
                <DropdownOverlay>
                  <ActionList>
                    {REPORTS_PRESETS.map(({ label, value }) => (
                      <ActionListItem key={value} title={label} value={value} />
                    ))}
                  </ActionList>
                </DropdownOverlay>
              </Dropdown>
            )}
            <Dropdown selectionType="multiple">
              <SelectInput
                label="Add Recipient's Details"
                placeholder="Select Email IDs"
                helpText="This is in addition to the email id already associated with this account"
                value={recipients}
                onChange={handleRecipients}
              />
              <DropdownOverlay>
                {availableEmails && Array.isArray(availableEmails) ? (
                  <ActionList>
                    {availableEmails.map((email) => (
                      <ActionListItem key={email} title={email} value={email} />
                    ))}
                  </ActionList>
                ) : (
                  <Box paddingX="spacing.5" paddingY="spacing.4">
                    <Text
                      size="medium"
                      type="normal"
                      contrast="low"
                      color="surface.text.normal.lowContrast"
                    >
                      No emails available
                    </Text>
                  </Box>
                )}
              </DropdownOverlay>
            </Dropdown>
          </Box>
          <ModalFooter>
            <Box marginRight="spacing.4">
              <Button
                isDisabled={isSubmitButtonLoading}
                size="medium"
                onClick={handleClose}
                variant="tertiary"
              >
                Cancel
              </Button>
            </Box>
            <Button
              isLoading={isSubmitButtonLoading}
              size="medium"
              onClick={handleSubmit}
              variant="primary"
              accessibilityLabel="Start Download"
            >
              Send Request
            </Button>
          </ModalFooter>
        </ReportModalWrapper>
      </Suspense>
    </div>
  );
};

export default ReportModal;
