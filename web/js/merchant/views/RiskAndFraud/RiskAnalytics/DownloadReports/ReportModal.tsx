import React, { useState } from 'react';
import {
  ActionList,
  ActionListItem,
  Box,
  Button,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  Text,
} from '@razorpay/blade/components';
import moment from 'moment';
import { COMMON_Z_INDEX } from 'common/constant';
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
import { ReportInitialState, ReportModalProps, SelectedRangeType } from './types';
import { getDurationCoveredInReports, getInitialState } from './utils';
import { trackEvent } from '../../common/trackEvents';
import { RISK_DECLINED } from '../constants';

const ReportModal: React.FC<ReportModalProps> = (props) => {
  const { entity, availableEmails, generatedBy, onCloseCallback, showNotification } = props;
  const { title, filename } = REPORT_MODAL_CONTENT[entity];

  const [dateRange, setDateRange] = useState<ReportInitialState>(() => getInitialState());
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
      setDateRange({ startDate: null, endDate: null, preset: selectedOption });
      return;
    }

    const { duration, unit } = selectedOption;
    const currentDate = moment().subtract(1, 'day');
    const endDate = moment(currentDate).startOf('day');
    const startDate = endDate.clone().subtract(duration, unit).startOf('day');
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
    const { startDate, endDate } = newDateRange;
    trackEvent({
      objectName: 'Download list - Custom Duration',
      actionName: 'Change',
      properties: {
        section: entity,
        dateRange: {
          startDate: startDate.unix(),
          endDate: endDate.unix(),
          preset: dateRange.preset,
        },
      },
    });
    setCustomDurationRange(newDateRange);
  };

  const handleRecipients = ({ values }) => setRecipients(values);

  const validateCustomDurationForPicker = ({ startDate, endDate }) => {
    if (endDate.diff(startDate, 'day') > 90) {
      return { error: 'Max allowed range is 90 days.' };
    } else {
      return true;
    }
  };

  const handleSubmit = async () => {
    const { preset } = dateRange;

    const payload = {
      generated_by: generatedBy,
      config_id: CONFIG_IDS[entity],
      emails: recipients?.length ? recipients : undefined,
      template_overrides: { file_meta: { extension: 'xlsx', filename: `${filename}-report` } },
      ...getDurationCoveredInReports({
        isCustomDurationEnabled: preset.value === 'custom',
        customDurationRange,
        dateRange,
      }),
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
          onClick={handleClose}
          emphasis="intense"
          icon={CloseIcon}
        />
      </ReportCloseButton>
      <Suspense minWidth={750}>
        <ReportModalWrapper width={625} height="auto">
          <ReportModalHeader>
            <Text size="large">{title}</Text>
            <Text variant="body" size="medium" weight="regular" color="surface.text.gray.muted">
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
            <Dropdown selectionType="single">
              <SelectInput
                label="Select Duration"
                helpText={preset.value !== 'custom' ? 'Select duration to cover in report.' : ''}
                placeholder="Select duration"
                necessityIndicator="required"
                value={preset.value}
                onChange={handlePreset}
              />
              <DropdownOverlay zIndex={COMMON_Z_INDEX.DROPDOWN_OVERLAY}>
                <ActionList>
                  {REPORTS_PRESETS.map(({ label, value }) => (
                    <ActionListItem key={value} title={label} value={value} />
                  ))}
                </ActionList>
              </DropdownOverlay>
            </Dropdown>

            {preset.value === 'custom' && (
              <DateTimeRangePicker
                label=""
                placeHolder="Select duration"
                helpText="Select duration to cover in report."
                onChange={handleCustomRange}
                value={customDurationRange}
                minDate={
                  entity !== RISK_DECLINED
                    ? moment().subtract(2, 'years')
                    : moment().subtract(6, 'months')
                }
                maxDate={moment().subtract(1, 'day')}
                validateRange={validateCustomDurationForPicker}
                necessityIndicator="required"
                disableTimeSelection
              />
            )}

            <Dropdown selectionType="multiple">
              <SelectInput
                label="Add Recipient's Details"
                placeholder="Select Email IDs"
                value={recipients}
                onChange={handleRecipients}
              />
              <DropdownOverlay zIndex={COMMON_Z_INDEX.DROPDOWN_OVERLAY}>
                {availableEmails && Array.isArray(availableEmails) ? (
                  <ActionList>
                    {availableEmails.map((email) => (
                      <ActionListItem key={email} title={email} value={email} />
                    ))}
                  </ActionList>
                ) : (
                  <Box paddingX="spacing.5" paddingY="spacing.4">
                    <Text size="medium" color="surface.text.gray.normal">
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
