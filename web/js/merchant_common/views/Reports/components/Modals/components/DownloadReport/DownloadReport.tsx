import React, { Fragment, useEffect, useState } from 'react';
import moment from 'moment';
import { BaseConfigType } from 'merchant_common/views/Reports/types/config';
import { DownloadReportModalPropsType, PredefinedDurationType } from './types';
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
  AsyncDropdown,
  Box,
  Badge,
  MailIcon,
  UserIcon,
} from 'merchant_common/views/Reports/components';
import {
  ModalFooter,
  ReportModalHeader,
  ScrollableModalContent,
} from 'merchant_common/views/Reports/components/Modals/styled';
import { SELECTED_DATE_RANGE_RENDER_FORMAT } from 'merchant_common/views/Reports/components/DateTimeRangePicker/constants';
import { SelectedRangeType } from 'merchant_common/views/Reports/components/DateTimeRangePicker/types';
import { preDefinedDurations } from './data';
import { fetchAccountsApi } from 'merchant/reducers/marketplace/accounts';
import { AccountType } from 'merchant_common/views/Reports/types/account';
import { MARKET_PLACE_CONFIG_TYPES } from 'merchant_common/views/Reports/configs';
import { downloadNewReport } from 'merchant_common/views/Reports/api/downloadModal';
import {
  REPORT_GENERATE_LOG_POST_FAILED,
  MANDATORY_FIELD_REQUIRED,
  REPORT_GENERATE_LOG_POST_SUCCESS,
  REPORT_GENERATE_LOG_POST_INVALID_RES,
} from 'merchant_common/views/Reports/constants/notifications';
import { CancelButtonContainer } from './styled';
import { trackDownloadModal } from 'merchant_common/views/Reports/configs/analytics.config';

export const DownloadReportModal = ({
  allReportConfigs,
  params,
  closeModal,
  availableAccounts,
  availableEmails,
  showNotification,
  startLogsPoll,
  stopLogsPoll,
  headers,
  generatedBy,
  dashboardType,
  parsePayloadBeforeSubmit,
  handlePageChange,
}: DownloadReportModalPropsType): JSX.Element => {
  //toggles.
  const [isCustomDurationEnabled, setCustomDurationEnabled] = useState<boolean>(false);
  const [showErrorInSection, setShowErrorInSection] = useState<number | undefined>();

  // section 1
  const [selectedConfig, setSelectedConfig] = useState<undefined | BaseConfigType>();
  const [saveReportAs, setSaveReportAs] = useState('');
  const [selectedFormat, setSelectedFormat] = useState<{
    label: string;
    value: string;
  }>();

  // section 2
  const [selectedPredefinedDurationRange, setSelectedPredefinedDurationRange] = useState<
    undefined | PredefinedDurationType
  >();
  const [customDurationRange, setCustomDurationRange] = useState<undefined | SelectedRangeType>();

  const [isRecipientsEnabled, setIsRecipientsEnabled] = useState(false);
  // section 3
  const [recipients, setRecipients] = useState<string[]>([]);

  const [isSubmitButtonLoading, setSubmitButtonLoading] = useState(false);

  const [selectedAccount, setSelectedAccount] = useState<AccountType>();

  const renderDurationInfo = () => {
    if (!isCustomDurationEnabled && selectedPredefinedDurationRange) {
      return selectedPredefinedDurationRange.label;
    } else {
      const { startDate, endDate } = customDurationRange ?? {};
      if (!startDate || !endDate) return null;
      if (moment.isMoment(startDate) && moment.isMoment(endDate)) {
        return `${moment(startDate).format(SELECTED_DATE_RANGE_RENDER_FORMAT)}   -   ${moment(
          endDate,
        ).format(SELECTED_DATE_RANGE_RENDER_FORMAT)}`;
      }
    }
    return '';
  };

  const validateDurationRange = (startDate, endDate) =>
    moment.isMoment(startDate) && moment.isMoment(endDate);

  const validateCustomDuration = () =>
    validateDurationRange(customDurationRange?.startDate, customDurationRange?.endDate);

  const validateDefaultDuration = () => {
    if (selectedPredefinedDurationRange?.label) {
      const { startDate, endDate } = selectedPredefinedDurationRange.value;
      return validateDurationRange(startDate, endDate);
    } else {
      return false;
    }
  };

  const validationsForEachSections = [
    Boolean(selectedConfig),
    isCustomDurationEnabled ? validateCustomDuration() : validateDefaultDuration(),
    Boolean(
      isRecipientsEnabled ? recipients && Array.isArray(recipients) && recipients.length : true,
    ),
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
    trackDownloadModal({
      actionName: 'Start Report Download Btn Clicked',
      dashboardType,
    });

    if (handleErrorStates()) {
      const payload = {
        config_id: selectedConfig?.id,
        start_time: isCustomDurationEnabled
          ? customDurationRange!.startDate.clone().unix()
          : selectedPredefinedDurationRange!.value.startDate.clone().unix(),
        end_time: isCustomDurationEnabled
          ? customDurationRange!.endDate.clone().unix()
          : selectedPredefinedDurationRange!.value.endDate.clone().unix(),
        emails: isRecipientsEnabled ? recipients : undefined,
        template_overrides:
          Boolean(selectedFormat?.value) || Boolean(saveReportAs)
            ? {
                file_meta: {
                  extension: Boolean(selectedFormat?.value) ? selectedFormat?.value : undefined,
                  filename: Boolean(saveReportAs) ? saveReportAs : undefined,
                },
              }
            : undefined,
      };

      const parsedPayload = parsePayloadBeforeSubmit(payload, {
        selectedConfig,
      });

      setSubmitButtonLoading(true);
      stopLogsPoll();
      downloadNewReport({
        generatedBy,
        headers,
        payload: parsedPayload,
        accountId:
          selectedConfig &&
          MARKET_PLACE_CONFIG_TYPES.includes(selectedConfig.type) &&
          !selectedAccount?.current
            ? selectedAccount?.id
            : undefined,
      })
        .then((data) => {
          handlePageChange(1);
          if (!data.success || !data.data || !data.data.id) {
            trackDownloadModal({
              actionName: 'Generate Report Req Failed',
              properties: payload as unknown as Record<string, unknown>,
              dashboardType,
            });
            return showNotification({
              type: 'error',
              message: REPORT_GENERATE_LOG_POST_INVALID_RES,
            });
          }

          trackDownloadModal({
            actionName: 'Generate Report Req Success',
            dashboardType,
          });

          showNotification({
            type: 'success',
            message: REPORT_GENERATE_LOG_POST_SUCCESS,
          });

          return closeModal();
        })
        .catch(() => {
          trackDownloadModal({
            actionName: 'Generate Report Req Failed',
            properties: payload as unknown as Record<string, unknown>,
            dashboardType,
          });

          return showNotification({
            type: 'error',
            message: REPORT_GENERATE_LOG_POST_FAILED,
          });
        })
        .finally(() => {
          setSubmitButtonLoading(false);
          return startLogsPoll();
        });
    } else {
      showNotification({
        type: 'error',
        message: MANDATORY_FIELD_REQUIRED,
      });

      trackDownloadModal({
        actionName: 'Report Download Validation Error',
        dashboardType,
      });
    }
    return null;
  };

  const validateCustomDurationForPicker = ({ startDate, endDate }) => {
    switch (true) {
      case endDate.diff(startDate, 'day') > 95:
        return {
          error: 'Max allowed range is 95 days.',
        };
      case Boolean(
        selectedConfig &&
          endDate.diff(startDate, 'day') > 31 &&
          selectedConfig?.template &&
          selectedConfig?.template.referred_accounts === 'all',
      ):
        return {
          error: 'Max allowed range is 31 days for aggregated partner reports.',
        };
      default:
        return true;
    }
  };

  const parseResData = (resp) => {
    const data = resp?.data?.items;
    if (data && data.length) {
      return data;
    }
    return [];
  };

  const customComponent = ({ name, id, email }: AccountType) => {
    return (
      <div aria-label={`${name} (${id})`}>
        <Text size="medium" variant="body" weight="bold">
          {`${name}`}
        </Text>
        <Box marginTop={'spacing.3'} display={'flex'} alignItems={'center'}>
          <Badge icon={UserIcon} variant="neutral">
            {id.includes('acc_') ? id.replace('acc_', '') : id}
          </Badge>
          <Badge icon={MailIcon} marginLeft={'spacing.3'} variant="neutral">
            {email}
          </Badge>
        </Box>
      </div>
    );
  };

  useEffect(() => {
    if (!params?.selectedConfig) return;
    const refConfig = allReportConfigs.find((data) => data.id === params.selectedConfig);
    setSelectedConfig(refConfig);
  }, [params]);

  useEffect(() => {
    if (!isRecipientsEnabled) {
      setRecipients([]);
      if (showErrorInSection === 2) {
        setShowErrorInSection(undefined);
      }
    }
  }, [isRecipientsEnabled]);

  useEffect(() => {
    trackDownloadModal({
      actionName: 'Download Report Modal Opened',
      dashboardType,
    });
  }, []);

  return (
    <Fragment>
      <ReportModalHeader>
        <Heading variant="regular">Download report for your business</Heading>
        <Text
          variant="body"
          size="medium"
          weight="regular"
          color="surface.text.subdued.lowContrast"
        >
          A new improved version of reports now available for you to download. You can now select
          the specific date and time period for which you would like to see the report.
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
            helpText={`${selectedConfig?.name ?? 'Report type'}, ${
              selectedFormat?.value ?? 'format'
            }`}
          >
            <Dropdown
              label="Select Report"
              helpText={
                selectedConfig?.description ?? 'Select report you want to receive report about.'
              }
              placeHolder="Select A Report"
              labelKey="name"
              onChange={setSelectedConfig}
              options={allReportConfigs}
              value={selectedConfig}
              validate={() => (showErrorInSection === 0 ? Boolean(selectedConfig) : true)}
              isSearchable
              isVirtualized
              itemHeight={36}
              ariaLabelBy="Select Report Field"
            />
            <TextInput
              label="Save Report As"
              placeHolder="Eg: Monthly Recon Report"
              value={saveReportAs}
              onChange={setSaveReportAs}
              helpText="Enter file name for your report."
              ariaLabel="Save Report As Input"
            />

            <Dropdown
              label="Select Format"
              placeHolder="Excel or CSV"
              options={[
                {
                  label: 'Excel',
                  value: 'xlsx',
                },
                {
                  label: 'CSV',
                  value: 'csv',
                },
              ]}
              labelKey="label"
              helpText="Select the format in which you want to receive the report in."
              onChange={setSelectedFormat}
              value={selectedFormat}
              ariaLabelBy="Select Format Field"
            />

            {selectedConfig &&
            MARKET_PLACE_CONFIG_TYPES.includes(selectedConfig.type) &&
            availableAccounts ? (
              <AsyncDropdown
                label="Select An Account"
                placeHolder="Account ID, Account Name..."
                helpText="Select a linked account, start by searching here."
                value={selectedAccount}
                promise={({ query }) => fetchAccountsApi(null, { q: query, search_hits: 1 })}
                isLoading={availableAccounts?.loading}
                defaultValue={availableAccounts?.accounts[0]}
                onChange={(val) => {
                  setSelectedAccount(val);
                  trackDownloadModal({
                    actionName: 'Account Selection Field Interactions',
                    properties: {
                      interaction_type: 'select',
                      selected_account: val,
                    },
                    dashboardType,
                  });
                }}
                parseData={parseResData}
                labelKey="name"
                ariaLabelBy="Select Account Field"
                renderCustomOption={customComponent}
              />
            ) : null}
          </CollapsibleFormSection>
          <CollapsibleFormSection
            title="What will you receive in this report?"
            helpText={renderDurationInfo() ?? 'Period of data, time, date etc.'}
            endComponent={{
              component: () => (
                <Switch
                  label="Custom"
                  value={isCustomDurationEnabled}
                  onChange={(bool) => {
                    setCustomDurationEnabled(bool);
                    trackDownloadModal({
                      actionName: 'Enable Custom Duration Switch Toggled',
                      properties: {
                        use_custom_duration: bool,
                      },
                      dashboardType,
                    });
                  }}
                />
              ),
              visible: 'on-active',
            }}
          >
            {isCustomDurationEnabled ? (
              <DateTimeRangePicker
                label="Select Duration"
                helpText="Choose a duration to cover in report."
                placeHolder="Select duration covered in each report"
                onChange={setCustomDurationRange}
                value={customDurationRange}
                allowSingleDateSelection
                disableFuture
                modifiers={{
                  INFO_WHEN_FUTURE_DISABLED: `*You can only download report containing data upto ${moment().format(
                    'MMMM Do, h A',
                  )}`,
                }}
                validate={validateCustomDurationForPicker}
              />
            ) : (
              <Dropdown
                label="Select Duration"
                helpText="Select duration to cover in report."
                placeHolder="Select duration covered in each report"
                value={selectedPredefinedDurationRange}
                options={preDefinedDurations}
                onChange={setSelectedPredefinedDurationRange}
                ariaLabelBy="Select Duration Field"
                labelKey="label"
                validate={() => (showErrorInSection === 1 ? validateDefaultDuration() : true)}
              />
            )}
          </CollapsibleFormSection>
          <CollapsibleFormSection
            title="Do you want this report in an email?"
            helpText={
              (isRecipientsEnabled ? validationsForEachSections[2] : false)
                ? recipients.join(', ')
                : "Add receiver's email addresses."
            }
            disabled={!isRecipientsEnabled}
            endComponent={{
              component: () => (
                <Switch
                  label="Yes"
                  value={isRecipientsEnabled}
                  onChange={(bool) => {
                    setIsRecipientsEnabled(bool);
                    trackDownloadModal({
                      actionName: 'Enable Emails Switch Toggled',
                      properties: {
                        enable_emails: bool,
                      },
                      dashboardType,
                    });
                  }}
                />
              ),
              visible: 'always',
            }}
          >
            <Dropdown
              label="Add Recipient's Details"
              helpText="Select report you want to receive report about."
              placeHolder="Start typing emails to add"
              value={recipients}
              onChange={setRecipients}
              shouldCloseDropdownOnSelect={false}
              validate={() => (showErrorInSection === 2 ? validationsForEachSections[2] : true)}
              isSearchable
              options={availableEmails && Array.isArray(availableEmails) ? availableEmails : []}
              shouldAllowMultiple
              isVirtualized
              itemHeight={36}
              ariaLabelBy="Add Recipient Field"
            />
          </CollapsibleFormSection>
        </CollapsibleForm>
        <ModalFooter>
          <CancelButtonContainer>
            <Button
              isDisabled={isSubmitButtonLoading}
              size="medium"
              onClick={closeModal}
              variant="tertiary"
            >
              Cancel
            </Button>
          </CancelButtonContainer>
          <Button
            isLoading={isSubmitButtonLoading}
            size="medium"
            onClick={handleSubmit}
            variant="primary"
            accessibilityLabel="Start Download"
          >
            Start Download
          </Button>
        </ModalFooter>
      </ScrollableModalContent>
    </Fragment>
  );
};
