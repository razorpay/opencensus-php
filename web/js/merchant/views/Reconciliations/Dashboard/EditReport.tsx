import React, { memo, useEffect, useState } from 'react';
import {
  Box,
  CloseIcon,
  Text,
  Heading,
  FileTextIcon,
  Accordion,
  AccordionItem,
  AccordionItemHeader,
  AccordionItemBody,
  CheckCircleIcon,
  IconButton,
} from '@razorpay/blade/components';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  SelectedColumn,
  ConfigForm,
  CompletedAccordionStepType,
  AccordionExpandedIndexType,
  ColumnSourceObjType,
  EditReportProps,
} from 'merchant/views/Reconciliations/Dashboard/types';
import { connect } from 'react-redux';
import { useNavigate, useParams } from 'react-router-dom';
import { compose } from 'redux';

import ErrorBoundary, { Teams } from 'common/new-ui/ErrorBoundary';
import SelectColumns from 'merchant/views/Reconciliations/Dashboard/SelectColumns';
import { createColumnSourceObj } from 'merchant/views/Reconciliations/Dashboard/utils';
import { RenderErrorLoadingOrChild } from 'merchant/views/Reconciliations/commonComponents';
import { showNotification } from 'merchant_common/reducers/notifications';

import { RECON_REPORTING_LIST_BASEURL } from './constants';
import { fetchReport, fetchProcessSourceColumns, updateReportConfig } from '../api';

const EditReport: React.FC<EditReportProps> = memo(({ showNotification }) => {
  const navigate = useNavigate();
  const { configId } = useParams();
  const queryClient = useQueryClient();

  const [hasCompletedCreateReportStep, setHasCompletedCreateReportStep] =
    useState<CompletedAccordionStepType>({
      sourceData: true,
      matchingFields: true,
      selectColumns: false,
    });
  const [accordionExpandedIndex, setAccordionExpandedIndex] = useState<AccordionExpandedIndexType>({
    sourceData: -1,
    matchingFields: -1,
    selectColumns: 0,
  });
  const [merchantProcessIds, setMerchantProcessIds] = useState<string[]>([]);
  const [columnSourceLevel, setColumnSourceLevel] = useState<ColumnSourceObjType>({});
  const [selectedColumnForReport, setSelectedColumnForReport] = useState<SelectedColumn[]>([]);
  const [selectedColumnCheckbox, setSelectedColumnCheckBox] = useState<Record<string, string[]>>(
    {},
  );

  const [configForm, setConfigForm] = useState<ConfigForm>({
    name: '',
    description: '',
  });

  const {
    data: reportDetailData,
    isLoading: isLoadingForReportDetails,
    isError: isErrorForReportDetails,
    isSuccess: isSuccessForReportDetails,
  } = useQuery({
    queryKey: ['report', configId],
    queryFn: () => fetchReport({ configId }),
  });

  const {
    data: columnsForMatchingFields,
    isLoading: isLoadingForColumnsForMatchingFields,
    isError: isErrorForColumnsForMatchingFields,
    isSuccess: isSuccessForColumnsForMatchingFields,
  } = useQuery({
    queryKey: ['processSourceColumns', merchantProcessIds],
    queryFn: () => fetchProcessSourceColumns({ processIds: merchantProcessIds }),
    enabled: merchantProcessIds.length > 0,
  });

  const {
    mutate: updateReportConfigMuatation,
    isSuccess: isSuccessForUpdateReport,
    isLoading: isLoadingForUpdateReport,
    isError: isErrorForUpdateReport,
  } = useMutation({
    mutationFn: () =>
      updateReportConfig({
        configId,
        selectedColumnForReport,
        previousReportConfigData: reportDetailData?.data,
        reportName: configForm.name,
      }),
  });

  const showSelectColumns =
    columnsForMatchingFields?.data?.source_cols.length && selectedColumnForReport.length;

  const goBackToReports = () => {
    navigate(RECON_REPORTING_LIST_BASEURL);
  };

  const createPreSelectedColumn = (reportDetailData) => {
    return reportDetailData?.data?.report_file_configs?.source_reporting_configs?.length
      ? reportDetailData.data.report_file_configs.source_reporting_configs?.flatMap((process) =>
          process?.column_mapping?.map((column) => ({
            id: column?.id
              ? column.id
              : `${process?.merchant_process_id || ''}-${process?.merchant_source_id || ''}-${
                  column?.source_column || ''
                }`,
            merchant_source_id: process?.merchant_source_id || '',
            merchant_process_id: process?.merchant_process_id || '',
            name: column?.source_column || '',
            merchant_process_name: process?.merchant_process_name || '',
            merchant_source_name: process?.merchant_source_name || '',
            editedName: column?.report_column || '',
          })),
        )
      : [];
  };

  const createPreSelectedColumnIds = (preSelectedColumn) => {
    return preSelectedColumn?.length
      ? preSelectedColumn.reduce((acc, curr) => {
          const key = `${curr.merchant_source_id}`;
          if (!acc[key]) {
            acc[key] = [];
          }
          acc[key].push(curr.id);
          return acc;
        }, {})
      : {};
  };

  useEffect(() => {
    if (isSuccessForReportDetails && reportDetailData) {
      const processIds =
        reportDetailData.data.merchant_processes?.map((process) => process.merchant_process_id) ||
        [];
      if (processIds.length) {
        setMerchantProcessIds(processIds);
      }

      if (reportDetailData.data.name) {
        setConfigForm((prevFormData) => ({ ...prevFormData, name: reportDetailData.data.name }));
      }
    }
  }, [isSuccessForReportDetails, reportDetailData]);

  useEffect(() => {
    if (isSuccessForColumnsForMatchingFields) {
      const columnSourceObj = createColumnSourceObj(columnsForMatchingFields?.data.source_cols);
      const preSelectedColumn = createPreSelectedColumn(reportDetailData);
      const preSelectedColumnIds = createPreSelectedColumnIds(preSelectedColumn);

      if (Object.keys(columnSourceObj).length) {
        setColumnSourceLevel(columnSourceObj);
      }
      if (Object.keys(preSelectedColumnIds).length) {
        setSelectedColumnCheckBox(preSelectedColumnIds);
      }
      if (preSelectedColumn.length) {
        setSelectedColumnForReport(preSelectedColumn);
      }
    }
  }, [isSuccessForColumnsForMatchingFields]);

  useEffect(() => {
    if (selectedColumnForReport.length > 0 && hasCompletedCreateReportStep.selectColumns === true) {
      updateReportConfigMuatation();
    }
    // close accordion
  }, [selectedColumnForReport, hasCompletedCreateReportStep]);

  useEffect(() => {
    if (isSuccessForUpdateReport) {
      queryClient.invalidateQueries({ queryKey: ['reportList'] });
      navigate(RECON_REPORTING_LIST_BASEURL);
    }
  }, [isSuccessForUpdateReport]);

  useEffect(() => {
    if (isErrorForUpdateReport) {
      showNotification({
        type: 'error',
        message: 'Unable to edit report config',
      });
    }
  }, [isErrorForUpdateReport]);

  return (
    <ErrorBoundary team={Teams.RECON_SAAS} resetOnProps>
      <Box padding="spacing.6">
        <Box
          display="flex"
          justifyContent="space-between"
          alignItems="center"
          paddingX="spacing.10"
        >
          <Heading as="h2" weight="semibold" size="large" color="surface.text.gray.normal">
            Edit Report
          </Heading>
          <IconButton
            icon={CloseIcon}
            accessibilityLabel="Close button"
            emphasis="intense"
            size="large"
            onClick={goBackToReports}
          />
        </Box>
        <RenderErrorLoadingOrChild
          isError={isErrorForReportDetails}
          isLoading={isLoadingForReportDetails}
        >
          <Box display="flex" flexDirection="column" gap="spacing.5" padding="spacing.10">
            <Accordion variant="filled" maxWidth="100%" defaultExpandedIndex={0}>
              <AccordionItem>
                <AccordionItemHeader>
                  <Box display="flex" gap="spacing.6">
                    <Box
                      display="flex"
                      justifyContent="center"
                      alignItems="center"
                      padding="spacing.5"
                      backgroundColor="surface.background.cloud.subtle"
                      borderRadius="small"
                    >
                      <FileTextIcon size="large" color="surface.icon.gray.normal" />
                    </Box>
                    <Box display="flex" flexDirection="column" gap="spacing.2">
                      <Heading as="h4" weight="semibold" color="surface.text.gray.normal">
                        Report Template
                      </Heading>
                      <Text color="surface.text.gray.muted">Select source data and format</Text>
                    </Box>
                  </Box>
                </AccordionItemHeader>
              </AccordionItem>
              <AccordionItemBody>
                <Accordion maxWidth="100%">
                  <AccordionItem isDisabled>
                    <AccordionItemHeader
                      title="Select source data"
                      subtitle="Add the processes from which you want to import source data"
                      leading={
                        <CheckCircleIcon size="large" color="feedback.icon.positive.intense" />
                      }
                    />
                  </AccordionItem>
                </Accordion>
                <Accordion maxWidth="100%">
                  <AccordionItem isDisabled>
                    <AccordionItemHeader
                      title="Select matching field"
                      subtitle="Confirm the columns that will help the files to stitch"
                      leading={
                        <CheckCircleIcon size="large" color="feedback.icon.positive.intense" />
                      }
                    />
                  </AccordionItem>
                </Accordion>
                <Accordion
                  maxWidth="100%"
                  expandedIndex={accordionExpandedIndex.selectColumns}
                  onExpandChange={({ expandedIndex }) => {
                    setAccordionExpandedIndex((precIndices) => ({
                      ...precIndices,
                      selectColumns: expandedIndex,
                    }));
                  }}
                >
                  <AccordionItem>
                    <AccordionItemHeader
                      title="Select Columns"
                      subtitle="Add, rename and reorder all the columns in your report"
                      leading={
                        <CheckCircleIcon size="large" color="feedback.icon.positive.intense" />
                      }
                    />
                    <AccordionItemBody>
                      {showSelectColumns ? (
                        <SelectColumns
                          columnList={columnsForMatchingFields?.data?.source_cols}
                          merchantSourcesColumn={columnSourceLevel}
                          isLoadingForColumnsForMatchingFields={
                            isLoadingForColumnsForMatchingFields
                          }
                          isErrorForColumnsForMatchingFields={isErrorForColumnsForMatchingFields}
                          selectedColumnForReport={selectedColumnForReport}
                          setSelectedColumnForReport={setSelectedColumnForReport}
                          isLoadingForCreateReport={null}
                          updateReportIsLoading={isLoadingForUpdateReport}
                          setHasCompletedCreateReportStep={setHasCompletedCreateReportStep}
                          configForm={configForm}
                          setConfigForm={setConfigForm}
                          selectedColumnCheckbox={selectedColumnCheckbox}
                          setSelectedColumnCheckBox={setSelectedColumnCheckBox}
                          type="edit"
                        />
                      ) : null}
                    </AccordionItemBody>
                  </AccordionItem>
                </Accordion>
              </AccordionItemBody>
            </Accordion>
          </Box>
        </RenderErrorLoadingOrChild>
      </Box>
    </ErrorBoundary>
  );
});

export default compose(connect(null, { showNotification }))(EditReport);
