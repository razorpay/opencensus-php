import React, { Fragment, useEffect, useState } from 'react';
import {
  Button,
  Dropdown,
  Heading,
  Text,
  MonthPicker,
  YearPicker,
  Link,
  ExternalLinkIcon,
  SelectInput,
  DropdownOverlay,
  ActionList,
  ActionListItem,
} from 'merchant_common/views/Reports/components';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import {
  ModalFooter,
  ReportModalHeader,
} from 'merchant_common/views/Reports/components/ReportModal/styled';
import { closeModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { showNotification } from 'merchant_common/reducers/notifications';
import { getReportsDashboardConfig } from 'merchant_common/views/Reports/configs/refDashboard.config';
import { getAvailableEmails } from 'merchant_common/views/Reports/utils/commonUtils';
import { DownloadReportModalPropsType } from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/types';
import { MarginDivider } from 'merchant_common/views/Reports/components/styled';
import { CloseModalButtonContainer, CustomDurationWrapper, FieldWrapper } from './styled';
import { CustomConfigType } from 'merchant_common/views/Reports/types';
import { MonthIndex } from 'merchant_common/views/Reports/components/types';

const mapStateToProps = ({ accounts, session }, { dashboardType }) => {
  const { user, mode } = session;
  const availableEmails = getAvailableEmails(user);
  const { availableAccounts, headers, customConfigs } = getReportsDashboardConfig(
    dashboardType,
    session,
    accounts,
  );
  const generatedBy = user.current;
  return {
    headers,
    customConfigs,
    availableEmails,
    availableAccounts,
    sessionMode: mode,
    generatedBy,
  };
};

const mapDispatchToProps = (dispatch) => ({
  closeModal: () => dispatch(closeModal()),
  showNotification: (payload) => dispatch(showNotification(payload)),
});

const DownloadCustomReport = connect(
  mapStateToProps,
  mapDispatchToProps,
)(
  ({
    params,
    closeModal,
    customConfigs,
    sessionMode,
    showNotification,
  }: DownloadReportModalPropsType): JSX.Element => {
    const [selectedConfig, setSelectedConfig] = useState<undefined | CustomConfigType>();
    const [month, setMonth] = useState<undefined | MonthIndex>();
    const [year, setYear] = useState<undefined | number>();
    const { theme } = useTheme();
    const [isSubmitButtonLoading, setSubmitButtonLoading] = useState(false);
    const [shouldShowError, setShouldShowError] = useState(false);

    const handleSubmit = () => {
      if (typeof month === 'number' && typeof year === 'number' && selectedConfig) {
        setSubmitButtonLoading(true);
        // faking loader for keeping the custom flow similar to other flow
        setTimeout(() => {
          window.open(
            `/${sessionMode}/reports/${selectedConfig?.id}/?year=${year}&month=${month + 1}`,
            '_blank',
          );
          closeModal();
        }, 1000);
      } else {
        setShouldShowError(true);
        showNotification({
          type: 'error',
          message: 'Please fill all the mandatory data.',
        });
      }
    };

    useEffect(() => {
      if (!params?.selectedConfig) return;
      const refConfig = customConfigs?.find((data) => data.id === params.selectedConfig);
      setSelectedConfig(refConfig);
    }, [params]);

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

        <MarginDivider theme={theme}>
          <FieldWrapper>
            <Dropdown selectionType="single">
              <SelectInput
                necessityIndicator="required"
                label="Select Report"
                onChange={({ values }) =>
                  customConfigs && setSelectedConfig(customConfigs[+values[0]])
                }
                placeholder="Select A Report"
                helpText={
                  selectedConfig?.description ?? 'Select report you want to receive report about.'
                }
                errorText="Mandatory Field: Select report you want to receive report about."
                value={customConfigs
                  ?.findIndex((customConfig) => customConfig.id === selectedConfig?.id)
                  .toString()}
              />
              <DropdownOverlay>
                <ActionList
                  options={customConfigs ?? ([] as CustomConfigType[])}
                  itemComponent={({ data: { name, id }, index }) => {
                    return <ActionListItem key={id} title={name} value={index.toString()} />;
                  }}
                />
              </DropdownOverlay>
            </Dropdown>
            {selectedConfig?.helpInfo && (
              <Text variant="caption" type="muted" weight="regular">
                {`${selectedConfig.helpInfo.info}`}{' '}
                <Link
                  size="small"
                  href={selectedConfig.helpInfo.link.href}
                  rel="noreferrer noopener"
                  target="_blank"
                  variant="anchor"
                  icon={ExternalLinkIcon}
                  iconPosition="right"
                >
                  {`${selectedConfig.helpInfo.link.label}`}
                </Link>
                .
              </Text>
            )}
          </FieldWrapper>
        </MarginDivider>

        <CustomDurationWrapper theme={theme}>
          <FieldWrapper>
            <MonthPicker
              helpText="Select month to cover in the report."
              errorText="Mandatory Field: Select month to cover in the report."
              label="Select Month"
              onChange={setMonth}
              placeHolder="Select month for your report"
              value={month}
              validate={() => (shouldShowError ? typeof month === 'number' : true)}
              necessityIndicator="required"
            />
          </FieldWrapper>
          <FieldWrapper>
            <YearPicker
              helpText="Select year to cover in the report."
              errorText="Mandatory Field: Select year to cover in the report."
              label="Select Year"
              onChange={setYear}
              placeHolder="Select year for your report"
              value={year}
              disableFuture
              validate={() => (shouldShowError ? typeof year === 'number' : true)}
              necessityIndicator="required"
            />
          </FieldWrapper>
        </CustomDurationWrapper>
        <ModalFooter>
          <CloseModalButtonContainer>
            <Button size="medium" onClick={closeModal} variant="tertiary">
              Cancel
            </Button>
          </CloseModalButtonContainer>

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
      </Fragment>
    );
  },
);

export default DownloadCustomReport;
