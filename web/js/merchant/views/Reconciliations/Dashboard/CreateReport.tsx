import React, { useEffect, useState, memo } from 'react';
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
import { useQuery, useMutation } from '@tanstack/react-query';
import {
  CompletedAccordionStepType,
  CreateReportProps,
  AccordionExpandedIndexType,
  ColumnSourceObjType,
  SelectedColumn,
  SelectedProcessItem,
} from 'merchant/views/Reconciliations/Dashboard/types';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { compose } from 'redux';

import ErrorBoundary, { Teams } from 'common/new-ui/ErrorBoundary';
import SelectColumns from 'merchant/views/Reconciliations/Dashboard//SelectColumns';
import ConfigProcessSelection from 'merchant/views/Reconciliations/Dashboard/ConfigProcessSelection';
import MatchingFieldKeySelection from 'merchant/views/Reconciliations/Dashboard/MatchingFieldKeySelection';
import ProcessSelectionModal from 'merchant/views/Reconciliations/Dashboard/ProcessSelectionModal';
import { createColumnSourceObj } from 'merchant/views/Reconciliations/Dashboard/utils';
import {
  fetchProcessSourceColumns,
  createReportConfig,
  joiningConfig,
} from 'merchant/views/Reconciliations/api';
import { showNotification } from 'merchant_common/reducers/notifications';

import { RECON_DASHBOARD_BASEURL, RECON_REPORTING_LIST_BASEURL } from './constants';

const CreateReport: React.FC<CreateReportProps> = memo(({ showNotification }) => {
  const [openProcessSelectionModal, setOpenProcessSelectionModal] = useState<boolean>(false);

  const [hasCompletedCreateReportStep, setHasCompletedCreateReportStep] =
    useState<CompletedAccordionStepType>({
      sourceData: false,
      matchingFields: false,
      selectColumns: false,
    });
  const [accordionExpandedIndex, setAccordionExpandedIndex] = useState<AccordionExpandedIndexType>({
    sourceData: 0,
    matchingFields: -1,
    selectColumns: -1,
  });

  const [selectedProcessesItem, setSelectedProcessesItem] = useState<SelectedProcessItem[]>([]);
  const [isProcessesSelectionDone, setIsProcessesSelectionDone] = useState<boolean>(false);
  const [matchingFieldKeys, setMatchingFieldKeys] = useState([]);
  const [selectedColumnForReport, setSelectedColumnForReport] = useState<SelectedColumn[]>([]);
  const [selectedColumnCheckbox, setSelectedColumnCheckBox] = useState<Record<string, string[]>>(
    {},
  );

  const [columnProcessLevel, setColumnProcessLevel] = useState({});
  const [columnSourceLevel, setColumnSourceLevel] = useState<ColumnSourceObjType>({});

  const [configForm, setConfigForm] = useState({
    name: '',
    description: '',
  });

  const {
    data: columnsForMatchingFields,
    isLoading: isLoadingForColumnsForMatchingFields,
    isError: isErrorForColumnsForMatchingFields,
    isSuccess: isSuccessForColumnsForMatchingFields,
  } = useQuery({
    queryKey: ['processSourceColumns', selectedProcessesItem],
    queryFn: () =>
      fetchProcessSourceColumns({ processIds: selectedProcessesItem.map((process) => process.id) }),
    enabled: selectedProcessesItem.length > 0 && hasCompletedCreateReportStep.sourceData,
  });

  const {
    data: joiningConfigData,
    mutate: joiningConfigMutation,
    isSuccess: isSuccessForJoiningConfig,
    isLoading: isLoadingForJoiningConfig,
  } = useMutation({
    mutationFn: () => joiningConfig({ selectedProcessesItem, matchingFieldKeys }),
  });

  const {
    mutate: createReportConfigMuatation,
    isSuccess: isSuccessForCreateReport,
    isLoading: isLoadingForCreateReport,
    isError: isErrorForCreateReport,
  } = useMutation({
    mutationFn: () =>
      createReportConfig({
        selectedProcessesItem,
        selectedColumnForReport,
        joiningConfig: joiningConfigData?.data?.joining_config,
        reportName: configForm.name,
      }),
  });

  const navigate = useNavigate();

  const isShowingMatchingFields =
    selectedProcessesItem &&
    selectedProcessesItem.length >= 2 &&
    hasCompletedCreateReportStep.sourceData;

  const showSelectColumns =
    (selectedProcessesItem &&
      selectedProcessesItem.length === 1 &&
      hasCompletedCreateReportStep.sourceData &&
      columnsForMatchingFields?.data?.source_cols?.length) ||
    (hasCompletedCreateReportStep.matchingFields &&
      columnsForMatchingFields?.data?.source_cols?.length);

  const goBackToDashboard = () => {
    navigate(`${RECON_DASHBOARD_BASEURL}/process`);
  };

  const removeProcessCardHandler = ({ processId }) => {
    setSelectedProcessesItem((prevSelectedItem) =>
      prevSelectedItem.filter((process) => process.id !== processId),
    );
  };

  const expandAccordionOnCompletion = () => {
    const { sourceData, matchingFields, selectColumns } = hasCompletedCreateReportStep;
    const hasMultipleProcesses = selectedProcessesItem.length >= 2;
    const hasSingleProcess = selectedProcessesItem.length === 1;

    if (sourceData && hasMultipleProcesses) {
      setAccordionExpandedIndex({ sourceData: -1, matchingFields: 0, selectColumns: -1 });
    }
    if (matchingFields && hasMultipleProcesses) {
      setAccordionExpandedIndex({ sourceData: -1, matchingFields: -1, selectColumns: 0 });
    }
    if (hasSingleProcess && sourceData) {
      setAccordionExpandedIndex({ sourceData: -1, matchingFields: -1, selectColumns: 0 });
    }
    if (selectColumns) {
      setAccordionExpandedIndex({ sourceData: -1, matchingFields: -1, selectColumns: -1 });
    }
  };

  const createColumnProcessObj = (sourceCols) => {
    return sourceCols.reduce((acc, curr) => {
      const { merchant_process_id, merchant_process_name } = curr;
      if (!acc[merchant_process_id]) {
        acc[merchant_process_id] = {
          id: merchant_process_id,
          name: merchant_process_name,
          columns: [],
        };
      }
      acc[merchant_process_id].columns.push({ ...curr });
      return acc;
    }, {});
  };

  useEffect(() => {
    if (selectedProcessesItem.length === 0) {
      setIsProcessesSelectionDone(false);
    }
  }, [selectedProcessesItem]);

  useEffect(() => {
    if (selectedColumnForReport.length > 0 && hasCompletedCreateReportStep.selectColumns === true) {
      createReportConfigMuatation();
    }
    expandAccordionOnCompletion();
  }, [selectedColumnForReport, hasCompletedCreateReportStep]);

  useEffect(() => {
    if (isSuccessForColumnsForMatchingFields) {
      const columnProcessObj = createColumnProcessObj(columnsForMatchingFields.data.source_cols);
      const columnSourceObj = createColumnSourceObj(columnsForMatchingFields.data.source_cols);
      if (Object.keys(columnProcessObj).length) {
        setColumnProcessLevel(columnProcessObj);
      }
      if (Object.keys(columnSourceObj).length) {
        setColumnSourceLevel(columnSourceObj);
      }
    }
  }, [isSuccessForColumnsForMatchingFields]);

  useEffect(() => {
    if (isSuccessForCreateReport) {
      navigate(RECON_REPORTING_LIST_BASEURL);
    }
  }, [isSuccessForCreateReport]);

  useEffect(() => {
    if (isErrorForCreateReport) {
      showNotification({
        type: 'error',
        message: 'Unable to create report config',
      });
    }
  }, [isErrorForCreateReport]);

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
            Create Report
          </Heading>
          <IconButton
            icon={CloseIcon}
            accessibilityLabel="Close button"
            emphasis="intense"
            size="large"
            onClick={goBackToDashboard}
          />
        </Box>
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
              <AccordionItemBody>
                <Accordion
                  variant="transparent"
                  maxWidth="100%"
                  expandedIndex={accordionExpandedIndex.sourceData}
                  onExpandChange={({ expandedIndex }) => {
                    setAccordionExpandedIndex((precIndices) => ({
                      ...precIndices,
                      sourceData: expandedIndex,
                    }));
                  }}
                >
                  <AccordionItem>
                    <AccordionItemHeader
                      title="Select source data"
                      subtitle="Add the processes from which you want to import source data"
                      leading={
                        hasCompletedCreateReportStep.sourceData ? (
                          <CheckCircleIcon size="large" color="feedback.icon.positive.intense" />
                        ) : null
                      }
                      trailing={
                        hasCompletedCreateReportStep.sourceData ? (
                          <Text color="interactive.text.primary.normal">Edit</Text>
                        ) : null
                      }
                    />
                    <AccordionItemBody>
                      <ConfigProcessSelection
                        selectedProcessesItem={selectedProcessesItem}
                        isProcessesSelectionDone={isProcessesSelectionDone}
                        setOpenProcessSelectionModal={setOpenProcessSelectionModal}
                        removeProcessCardHandler={removeProcessCardHandler}
                        setHasCompletedCreateReportStep={setHasCompletedCreateReportStep}
                      />
                    </AccordionItemBody>
                  </AccordionItem>
                </Accordion>
                {isShowingMatchingFields ? (
                  <Accordion
                    variant="transparent"
                    maxWidth="100%"
                    expandedIndex={accordionExpandedIndex.matchingFields}
                    onExpandChange={({ expandedIndex }) => {
                      setAccordionExpandedIndex((precIndices) => ({
                        ...precIndices,
                        matchingFields: expandedIndex,
                      }));
                    }}
                  >
                    <AccordionItem>
                      <AccordionItemHeader
                        title="Select matching field"
                        subtitle="Confirm the columns that will help the files to stitch"
                        leading={
                          hasCompletedCreateReportStep.matchingFields ? (
                            <CheckCircleIcon size="large" color="feedback.icon.positive.intense" />
                          ) : null
                        }
                        trailing={
                          hasCompletedCreateReportStep.matchingFields ? (
                            <Text color="interactive.text.primary.normal">Edit</Text>
                          ) : null
                        }
                      />
                      <AccordionItemBody>
                        <MatchingFieldKeySelection
                          processes={columnProcessLevel}
                          isLoadingForColumnsForMatchingFields={
                            isLoadingForColumnsForMatchingFields
                          }
                          isErrorForColumnsForMatchingFields={isErrorForColumnsForMatchingFields}
                          setHasCompletedCreateReportStep={setHasCompletedCreateReportStep}
                          matchingFieldKeys={matchingFieldKeys}
                          setMatchingFieldKeys={setMatchingFieldKeys}
                          joiningConfigData={joiningConfigData}
                          joiningConfigMutation={joiningConfigMutation}
                          isSuccessForJoiningConfig={isSuccessForJoiningConfig}
                          isLoadingForJoiningConfig={isLoadingForJoiningConfig}
                        />
                      </AccordionItemBody>
                    </AccordionItem>
                  </Accordion>
                ) : null}
                {showSelectColumns ? (
                  <Accordion
                    variant="transparent"
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
                          hasCompletedCreateReportStep.selectColumns ? (
                            <CheckCircleIcon size="large" color="feedback.icon.positive.intense" />
                          ) : null
                        }
                        trailing={
                          hasCompletedCreateReportStep.selectColumns ? (
                            <Text color="interactive.text.primary.normal">Edit</Text>
                          ) : null
                        }
                      />
                      <AccordionItemBody>
                        <SelectColumns
                          columnList={columnsForMatchingFields.data.source_cols}
                          merchantSourcesColumn={columnSourceLevel}
                          isLoadingForColumnsForMatchingFields={
                            isLoadingForColumnsForMatchingFields
                          }
                          isErrorForColumnsForMatchingFields={isErrorForColumnsForMatchingFields}
                          selectedColumnForReport={selectedColumnForReport}
                          setSelectedColumnForReport={setSelectedColumnForReport}
                          isLoadingForCreateReport={isLoadingForCreateReport}
                          setHasCompletedCreateReportStep={setHasCompletedCreateReportStep}
                          configForm={configForm}
                          setConfigForm={setConfigForm}
                          selectedColumnCheckbox={selectedColumnCheckbox}
                          setSelectedColumnCheckBox={setSelectedColumnCheckBox}
                          type="create"
                        />
                      </AccordionItemBody>
                    </AccordionItem>
                  </Accordion>
                ) : null}
              </AccordionItemBody>
            </AccordionItem>
          </Accordion>
        </Box>
      </Box>
      <ProcessSelectionModal
        openProcessSelectionModal={openProcessSelectionModal}
        onClose={() => setOpenProcessSelectionModal(false)}
        setOpenProcessSelectionModal={setOpenProcessSelectionModal}
        selectedProcessesItem={selectedProcessesItem}
        setSelectedProcessesItem={setSelectedProcessesItem}
        setIsProcessesSelectionDone={setIsProcessesSelectionDone}
      />
    </ErrorBoundary>
  );
});

export default compose(connect(null, { showNotification }))(CreateReport);
