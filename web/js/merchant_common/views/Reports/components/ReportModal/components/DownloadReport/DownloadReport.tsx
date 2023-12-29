import React, { Fragment, useEffect, useState } from 'react';
import moment from 'moment';

import { BaseConfigType } from 'merchant_common/views/Reports/types/config';
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
  SelectInput,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  MultiSelectDropdown,
  Box,
  Badge,
  MailIcon,
  UserIcon,
} from 'merchant_common/views/Reports/components';
import {
  ModalFooter,
  ReportModalHeader,
  ScrollableModalContent,
} from 'merchant_common/views/Reports/components/ReportModal/styled';
import { SelectedRangeType } from 'merchant_common/views/Reports/components/DateTimeRangePicker/types';
import { fetchAccountsApi } from 'merchant/reducers/marketplace/accounts';
import { AccountType } from 'merchant_common/views/Reports/types/account';
import { MARKET_PLACE_CONFIG_TYPES } from 'merchant_common/views/Reports/configs';
import {
  downloadNewReport,
  getBatchIds,
  getPaymentPagesFileUploadPages,
} from 'merchant_common/views/Reports/api/downloadModal';
import {
  REPORT_GENERATE_LOG_POST_FAILED,
  MANDATORY_FIELD_REQUIRED,
  REPORT_GENERATE_LOG_POST_SUCCESS,
  REPORT_GENERATE_LOG_POST_INVALID_RES,
} from 'merchant_common/views/Reports/constants/notifications';
import { trackDownloadModal } from 'merchant_common/views/Reports/configs/analytics.config';
import { getFormattedDate } from 'merchant_common/views/Reports/components/DateTimeRangePicker/utils';
import { Delimiter, Format } from 'merchant_common/views/Reports/types';

import { preDefinedDurations } from './data';
import { CancelButtonContainer } from './styled';
import {
  BatchPage,
  DownloadReportModalPropsType,
  PredefinedDurationType,
  PaymentStatus,
  BatchId,
} from './types';
import { Formats } from './components/Formats';
import {
  ALL_OPTION,
  CONFIG_TYPE_BATCH_PAGES,
  PAYMENT_STATUS_OPTIONS,
  BATCH_PAYMENT_PAGE_CUSTOMER_REPORT,
  BATCH_PAYMENT_PAGE_PAYMENT_REPORT,
  ERROR_IN_FIRST_SECTION,
  ERROR_IN_SECOND_SECTION,
  ERROR_IN_THIRD_SECTION,
  ERROR_IN_FOURTH_SECTION,
} from './constants';
import {
  formatBatchIds,
  getOptionsAccordingToAllOptionSelected,
  parseBatchPages,
} from './Utils/batchPaymentPages';
import { generatePayload } from './Utils/makePayload';
import {
  isBatchSectionValid,
  isDurationSectionValid,
  isRecipientsSectionValid,
  isReportSectionValid,
} from './Utils/sectionValidators';
import {
  isBatchIdsFieldValid,
  isCustomDurationFieldValid,
  isDefaultDurationFieldValid,
  isPaymentStatusFieldValid,
} from './Utils/fieldValidators';

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
  defaultHeaders,
  generatedBy,
  dashboardType,
  parsePayloadBeforeSubmit,
  handlePageChange,
  resetLogsPollOnSubmit = true,
  availableFormats,
}: DownloadReportModalPropsType): JSX.Element => {
  //toggles.
  const [isCustomDurationEnabled, setCustomDurationEnabled] = useState<boolean>(false);
  const [showErrorInSection, setShowErrorInSection] = useState<number | undefined>();

  // section 1
  const [selectedConfig, setSelectedConfig] = useState<undefined | BaseConfigType>();
  const [saveReportAs, setSaveReportAs] = useState('');
  const [selectedFormat, setSelectedFormat] = useState<Format>();
  const [selectedDelimiter, setSelectedDelimiter] = useState<Delimiter>();

  // Conditional section 2
  const [batchIds, setBatchIds] = useState<BatchId[]>([]);
  const [isBatchIdsLoading, setIsBatchIdsLoading] = useState(false);

  const [selectedBatchPage, setSelectedBatchPage] = useState<BatchPage>();
  const [selectedBatchIds, setSelectedBatchIds] = useState<BatchId[]>([]);
  const [selectedPaymentStatus, setSelectedPaymentStatus] = useState<PaymentStatus[]>([
    PAYMENT_STATUS_OPTIONS[0],
  ]);

  // section 3
  const [selectedPredefinedDurationRange, setSelectedPredefinedDurationRange] = useState<
    undefined | PredefinedDurationType
  >();
  const [customDurationRange, setCustomDurationRange] = useState<undefined | SelectedRangeType>();

  // section 4
  const [isRecipientsEnabled, setIsRecipientsEnabled] = useState(false);
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
        return getFormattedDate({ startDate, endDate }, false);
      }
    }
    return '';
  };

  const validationsForEachSections = [
    isReportSectionValid({
      selectedConfig,
      selectedFormat,
      selectedDelimiter,
    }),
    isBatchSectionValid({
      selectedConfig,
      selectedBatchPage,
      selectedBatchIds,
      selectedPaymentStatus,
      batchIds,
    }),
    isDurationSectionValid({
      selectedConfig,
      customDurationRange,
      selectedPredefinedDurationRange,
      isCustomDurationEnabled,
    }),
    isRecipientsSectionValid({ isRecipientsEnabled, recipients }),
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
      const payload = generatePayload({
        selectedConfig,
        saveReportAs,
        selectedFormat,
        selectedDelimiter,
        selectedBatchPage,
        selectedBatchIds,
        selectedPaymentStatus,
        customDurationRange,
        selectedPredefinedDurationRange,
        recipients,
        isCustomDurationEnabled,
        isRecipientsEnabled,
      });

      const parsedPayload = parsePayloadBeforeSubmit(payload, {
        selectedConfig,
      });

      setSubmitButtonLoading(true);

      if (resetLogsPollOnSubmit) stopLogsPoll();

      downloadNewReport({
        generatedBy,
        headers: headers ?? defaultHeaders,
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
          if (resetLogsPollOnSubmit) return startLogsPoll();
          return null;
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
          {name}
        </Text>
        <Box marginTop="spacing.3" display="flex" alignItems="center">
          <Badge icon={UserIcon} variant="neutral">
            {id}
          </Badge>
          <Badge icon={MailIcon} marginLeft="spacing.3" variant="neutral">
            {email}
          </Badge>
        </Box>
      </div>
    );
  };

  const customBatchPagesOption = ({ title, id }) => {
    return (
      <Box aria-label={`${title}-${id}`}>
        <Text
          size="medium"
          type="normal"
          contrast="low"
          color="surface.text.normal.lowContrast"
          truncateAfterLines={1}
        >
          {`${title} - ${id}`}
        </Text>
      </Box>
    );
  };

  useEffect(() => {
    if (!params?.selectedConfig) return;

    const refConfig = allReportConfigs.find((data) => data.id === params.selectedConfig);
    setSelectedConfig(refConfig);
  }, [params, allReportConfigs]);

  useEffect(() => {
    if (!isRecipientsEnabled) {
      setRecipients([]);
      if (showErrorInSection === ERROR_IN_FOURTH_SECTION) {
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

  const handleConfigChange = ({ values }) => {
    const config = allReportConfigs[+values[0]];

    // Set the state only when config options change.
    if (config?.id !== selectedConfig?.id) {
      setSelectedConfig(allReportConfigs[+values[0]]);

      if (selectedConfig?.type === CONFIG_TYPE_BATCH_PAGES) {
        // Empty out the prev batch page selected.
        setSelectedBatchPage(undefined);

        if (showErrorInSection === ERROR_IN_SECOND_SECTION) {
          setShowErrorInSection(undefined);
        }
      }
    }
  };

  const handleIsCustomDurationEnabled = (bool: boolean) => {
    setCustomDurationEnabled(bool);

    trackDownloadModal({
      actionName: 'Enable Custom Duration Switch Toggled',
      properties: {
        use_custom_duration: bool,
      },
      dashboardType,
    });
  };

  const handleBatchPaymentPageChange = async (val: BatchPage): Promise<void> => {
    setSelectedBatchPage(val);

    if (
      selectedConfig?.name === BATCH_PAYMENT_PAGE_CUSTOMER_REPORT &&
      val.id !== selectedBatchPage?.id
    ) {
      try {
        setBatchIds([]);
        setSelectedBatchIds([]);
        setIsBatchIdsLoading(true);

        const res = await getBatchIds(val.id);

        if (Array.isArray(res?.data) && Boolean(res.data.length)) {
          setBatchIds(formatBatchIds(res?.data));
          setSelectedBatchIds([ALL_OPTION]);
        }
      } catch (error: any) {
        showNotification({
          type: 'error',
          message: error?.errors?.join(' ') ?? 'Error in getting batch ids',
        });
      } finally {
        setIsBatchIdsLoading(false);
      }
    }
  };

  const handlePaymentStatusChange = (selectedValues: PaymentStatus[]): void => {
    const values = getOptionsAccordingToAllOptionSelected<PaymentStatus[]>({
      currSelectedOptions: selectedValues,
      prevSelectedOptions: selectedPaymentStatus,
    });

    setSelectedPaymentStatus(values);
  };

  const handleBatchIdChange = (selectedValues: BatchId[]): void => {
    const values = getOptionsAccordingToAllOptionSelected<BatchId[]>({
      currSelectedOptions: selectedValues,
      prevSelectedOptions: selectedBatchIds || [],
    });

    setSelectedBatchIds(values);
  };

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
          disableSectionsExpandOnError
          defaultOpen={0}
          style={{
            marginTop: 20,
          }}
        >
          <CollapsibleFormSection
            title="What report is this?"
            helpText={`${selectedConfig?.name ?? 'Report type'}`}
          >
            <Dropdown selectionType="single">
              <SelectInput
                necessityIndicator="required"
                label="Select Report"
                onChange={handleConfigChange}
                placeholder="Select A Report"
                validationState={
                  showErrorInSection === ERROR_IN_FIRST_SECTION && !Boolean(selectedConfig)
                    ? 'error'
                    : 'none'
                }
                helpText={
                  selectedConfig?.description ?? 'Select report you want to receive report about.'
                }
                value={allReportConfigs.findIndex((e) => e.id === selectedConfig?.id).toString()}
                errorText="Mandatory Field: Select report you want to receive report about."
              />
              <DropdownOverlay>
                <ActionList
                  itemComponent={({ data: { id, name }, index }) => (
                    <ActionListItem key={id} title={name} value={index.toString()} />
                  )}
                  options={allReportConfigs}
                />
              </DropdownOverlay>
            </Dropdown>

            <TextInput
              label="Save Report As"
              placeholder="Eg: Monthly Recon Report"
              value={saveReportAs}
              onChange={({ value }) => setSaveReportAs(value ?? '')}
              helpText="Enter file name for your report."
              necessityIndicator="optional"
            />

            <Formats
              availableFormats={availableFormats}
              selectedFormat={selectedFormat}
              selectedDelimiter={selectedDelimiter}
              setSelectedFormat={setSelectedFormat}
              setSelectedDelimiter={setSelectedDelimiter}
              showErrorInSection={showErrorInSection}
            />

            {selectedConfig &&
            MARKET_PLACE_CONFIG_TYPES.includes(selectedConfig.type) &&
            availableAccounts ? (
              <AsyncDropdown
                label="Select An Account"
                placeHolder="Account ID, Account Name..."
                helpText="Select a linked account, start by searching here."
                errorText="Mandatory Field: Select a linked account, start by searching here."
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
                necessityIndicator="required"
              />
            ) : null}
          </CollapsibleFormSection>

          {selectedConfig?.type === CONFIG_TYPE_BATCH_PAGES ? (
            <CollapsibleFormSection
              title="Which details should be part of this report?"
              helpText="Select the Batch Payment Page, Batch and Status for which the report should be generated"
            >
              <AsyncDropdown
                label="Select Batch Payment Page"
                placeHolder="Batch Page ID"
                helpText="Select the page for which you want to download the data."
                errorText="Mandatory Field: Select the page for which you want to download the data."
                value={selectedBatchPage}
                defaultValue={selectedBatchPage}
                promise={({ query }) => getPaymentPagesFileUploadPages({ title: query })}
                validate={() =>
                  showErrorInSection === ERROR_IN_SECOND_SECTION ? Boolean(selectedBatchPage) : true
                }
                onChange={handleBatchPaymentPageChange}
                parseData={parseBatchPages}
                labelKey="id"
                ariaLabelBy="Select Batch Payment Page"
                renderCustomOption={customBatchPagesOption}
                necessityIndicator="required"
              />

              {selectedConfig?.name === BATCH_PAYMENT_PAGE_CUSTOMER_REPORT &&
              selectedBatchPage?.id ? (
                <MultiSelectDropdown
                  label="Select Batch"
                  helpText="Select one or more batch IDs from the dropdown or type the batch ID to search and select."
                  errorText="Mandatory Field: Select one or more batch IDs from the dropdown or type the batch ID to search and select."
                  placeHolder={
                    batchIds.length === 0
                      ? 'No batches available'
                      : 'Please enter any batch ID you want to search and select it from the list.'
                  }
                  value={selectedBatchIds}
                  onChange={handleBatchIdChange}
                  shouldCloseDropdownOnSelect={false}
                  validate={() =>
                    showErrorInSection === ERROR_IN_SECOND_SECTION
                      ? isBatchIdsFieldValid({ selectedBatchPage, selectedBatchIds, batchIds })
                      : true
                  }
                  isSearchable
                  options={batchIds}
                  labelKey="label"
                  isVirtualized
                  isLoading={isBatchIdsLoading}
                  itemHeight={36}
                  ariaLabelBy="Select Batch"
                  necessityIndicator="required"
                  isDisabled={!isBatchIdsLoading && batchIds.length === 0}
                />
              ) : null}

              {selectedConfig?.name === BATCH_PAYMENT_PAGE_CUSTOMER_REPORT ? (
                <MultiSelectDropdown
                  label="Select Payment Status"
                  helpText="Filter the report results by selecting the payment status."
                  errorText="Mandatory Field: Filter the report results by selecting the payment status."
                  placeHolder="Select payment status from the list."
                  value={selectedPaymentStatus}
                  onChange={handlePaymentStatusChange}
                  shouldCloseDropdownOnSelect={false}
                  validate={() =>
                    showErrorInSection === ERROR_IN_SECOND_SECTION
                      ? isPaymentStatusFieldValid(selectedPaymentStatus)
                      : true
                  }
                  options={PAYMENT_STATUS_OPTIONS}
                  labelKey="label"
                  ariaLabelBy="Select Payment Status"
                  necessityIndicator="required"
                />
              ) : null}
            </CollapsibleFormSection>
          ) : (
            <></>
          )}

          {selectedConfig?.name !== BATCH_PAYMENT_PAGE_PAYMENT_REPORT ? (
            <CollapsibleFormSection
              title="What will you receive in this report?"
              helpText={renderDurationInfo() ?? 'Period of data, time, date etc.'}
              endComponent={{
                component: () => (
                  <Switch
                    label="Custom"
                    value={isCustomDurationEnabled}
                    onChange={handleIsCustomDurationEnabled}
                  />
                ),
                visible: 'on-active',
              }}
            >
              {isCustomDurationEnabled ? (
                <DateTimeRangePicker
                  label="Select Duration"
                  helpText="Choose a duration to cover in report."
                  errorText="Mandatory Field: Choose a duration to cover in report."
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
                  validationState={
                    showErrorInSection === ERROR_IN_THIRD_SECTION
                      ? isCustomDurationFieldValid(customDurationRange)
                      : true
                  }
                  validateRange={validateCustomDurationForPicker}
                  necessityIndicator="required"
                  minutesInterval={5}
                />
              ) : (
                <Dropdown selectionType="single">
                  <SelectInput
                    label="Select Duration"
                    helpText="Select duration to cover in report."
                    placeholder="Select duration covered in each report"
                    necessityIndicator="required"
                    onChange={({ values }) =>
                      setSelectedPredefinedDurationRange(preDefinedDurations[+values[0]])
                    }
                    validationState={
                      showErrorInSection === ERROR_IN_THIRD_SECTION
                        ? isDefaultDurationFieldValid(selectedPredefinedDurationRange)
                          ? 'none'
                          : 'error'
                        : 'none'
                    }
                    errorText="Mandatory Field: Select duration to cover in report."
                    value={preDefinedDurations
                      .findIndex((e) => e.value === selectedPredefinedDurationRange?.value)
                      .toString()}
                  />
                  <DropdownOverlay>
                    <ActionList
                      options={preDefinedDurations}
                      itemComponent={({ data, index }) => (
                        <ActionListItem
                          key={data.label}
                          title={data.label}
                          value={index.toString()}
                          testID={data.label}
                        />
                      )}
                    />
                  </DropdownOverlay>
                </Dropdown>
              )}
            </CollapsibleFormSection>
          ) : (
            <></>
          )}

          <CollapsibleFormSection
            title="Do you want this report in an email?"
            helpText={
              (isRecipientsEnabled ? validationsForEachSections[ERROR_IN_FOURTH_SECTION] : false)
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
            <MultiSelectDropdown
              label="Add Recipient's Details"
              helpText="Select report you want to receive report about."
              errorText="Mandatory Field: Select report you want to receive report about."
              placeHolder="Start typing emails to add"
              value={recipients}
              onChange={setRecipients}
              shouldCloseDropdownOnSelect={false}
              validate={() =>
                showErrorInSection === ERROR_IN_FOURTH_SECTION
                  ? validationsForEachSections[ERROR_IN_FOURTH_SECTION]
                  : true
              }
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
