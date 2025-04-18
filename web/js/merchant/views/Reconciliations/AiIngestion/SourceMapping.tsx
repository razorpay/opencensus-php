import React, { useState, useEffect } from 'react';
import { Box, Heading, Text, Badge, Button, ArrowRightIcon } from '@razorpay/blade/components';
import { useMutation, useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { compose } from 'redux';

import ColumnMappingTable from 'merchant/views/Reconciliations/AiIngestion/ColumnMappingTable';
import {
  createMlReconConfig,
  fetchMlConfig,
  generateMlReconConfig,
} from 'merchant/views/Reconciliations/api';
import { RenderErrorLoadingOrChild } from 'merchant/views/Reconciliations/commonComponents';
import { showNotification } from 'merchant_common/reducers/notifications';

import type { SourceMappingProps } from 'merchant/views/Reconciliations/AiIngestion/types';
import { COLUMN_SET_CONFIGS } from 'merchant/views/Reconciliations/AiIngestion/constant';

const SourceMapping: React.FC<SourceMappingProps> = ({
  sourcesData,
  processName,
  mlConfigIds,
  isGoBackClicked,
  setMlConfigIds,
  setAiIngestionStages,
  showNotification,
}) => {
  const [tableData, setTableData] = useState<Record<string, Array<any>>>({
    id_sets: [],
    amount_sets: [],
    date_sets: [],
    other_sets: [],
  });
  const [sourceColumnList, setSourceColumnList] = useState({});

  const [isPolling, setIsPolling] = useState(false);

  const {
    data: createConfigData,
    isLoading: isLoadingForCreateConfig,
    isError: isErrorForCreateConfig,
    isSuccess: isSuccessForCreateConfig,
  } = useQuery({
    queryKey: ['ml config'],
    queryFn: () => createMlReconConfig({ sources: sourcesData, processName }),
    enabled: !isGoBackClicked && mlConfigIds.sessionId === '' && mlConfigIds.auditLogId === '',
  });

  const {
    data: fetchConfigData,
    isLoading: isLoadingForFetchConfig,
    isError: isErrorForFetchConfig,
    isSuccess: isSuccessForFetchConfig,
  } = useQuery({
    queryKey: ['fetch config', mlConfigIds.auditLogId],
    queryFn: () =>
      fetchMlConfig({ sessionId: mlConfigIds.sessionId, auditLogId: mlConfigIds.auditLogId }),
    enabled: mlConfigIds.sessionId !== '' && mlConfigIds.auditLogId !== '',
    refetchInterval: isPolling ? 10000 : false,
  });

  const {
    data: generateMlConfigData,
    mutate: generateMlConfigMutation,
    isLoading: isLoadingForGenerateMlConfig,
    isError: isErrorForGenerateMlConfig,
    isSuccess: isSuccessForGenerateMlConfig,
  } = useMutation({
    mutationFn: ({
      columnConfig,
      auditLogId,
      created,
    }: {
      columnConfig: any[];
      auditLogId: string;
      created: boolean;
    }) => generateMlReconConfig({ columnConfig, auditLogId, created }),
  });

  const createEmptyRow = () => {
    return Array.isArray(fetchConfigData?.data?.column_config)
      ? fetchConfigData.data.column_config.reduce((entry, cfg) => {
          if (cfg?.merchant_source_id) {
            entry[cfg.merchant_source_id] = {};
          }
          return entry;
        }, {})
      : {};
  };

  const addNewRow = ({ tableName }) => {
    if (['date_sets'].includes(tableName)) {
      if (tableData[tableName]?.length >= 1) {
        showNotification({
          type: 'error',
          message: `Only one column is allowed for ${tableName} column for unique mapping`,
        });
        return;
      }
    }

    setTableData((prevData) => ({
      ...prevData,
      [tableName]: [...(prevData[tableName] || []), createEmptyRow()],
    }));
  };

  const deleteRow = ({ tableName, rowIndex }) => {
    setTableData((prevData) => {
      const tableLength = prevData[tableName]?.length || 0;

      if (tableLength === 1) {
        showNotification({
          type: 'error',
          message: `At least one column is required for ${tableName} column`,
        });
        return prevData;
      }

      return {
        ...prevData,
        [tableName]: prevData[tableName].filter((_, idx) => idx !== rowIndex),
      };
    });
  };

  const handleOnChange = ({ tableName, sourceId, index, values }) => {
    setTableData((prevTableData) => {
      const updatedTable = [...prevTableData[tableName]];
      updatedTable[index] = {
        ...updatedTable[index],
        [sourceId]: values,
      };

      return {
        ...prevTableData,
        [tableName]: updatedTable,
      };
    });
  };

  const confirmSourcePredictedColumn = ({ tableData }) => {
    if (!tableData.id_sets?.length) {
      showNotification({
        type: 'error',
        message: 'At least one column is required for the ID column',
      });
      return;
    }

    const nonEmptyDateSets =
      tableData.date_sets?.filter((column) => Object.keys(column).length > 0) || [];

    if (nonEmptyDateSets.length !== 1) {
      showNotification({
        type: 'error',
        message: 'Exactly one non-empty column is required for the Date column',
      });
      return;
    }

    const cleanedData = Object.fromEntries(
      Object.entries(tableData)
        .map(([key, value]) => [
          key,
          Array.isArray(value) ? value.filter((item) => Object.keys(item).length > 0) : value,
        ])
        .filter(([_, value]) => (Array.isArray(value) ? value.length > 0 : true)),
    );

    const columnConfig = fetchConfigData.data.column_config.reduce((acc, cfg) => {
      acc[cfg.merchant_source_id] = {
        merchant_source_id: cfg.merchant_source_id,
        merchant_source_name: cfg.merchant_source_name,
        id_matching_column_sets: [],
        amount_matching_column_sets: [],
        date_matching_column_sets: [],
        other_matching_column_sets: [],
      };
      return acc;
    }, {});

    const mergedData = Object.entries(columnConfig).reduce((acc, [key, value]) => {
      acc[key] = {
        ...(value as any),
        id_matching_column_sets: (cleanedData.id_sets?.map((item) => item[key]) || []).filter(
          Boolean,
        ),
        amount_matching_column_sets: (
          cleanedData.amount_sets?.map((item) => item[key]) || []
        ).filter(Boolean),
        date_matching_column_sets: (cleanedData.date_sets?.map((item) => item[key]) || []).filter(
          Boolean,
        ),
        other_matching_column_sets: (cleanedData.other_sets?.map((item) => item[key]) || []).filter(
          Boolean,
        ),
      };
      return acc;
    }, {});

    generateMlConfigMutation({
      columnConfig: Object.values(mergedData),
      auditLogId: mlConfigIds.auditLogId,
      created: fetchConfigData.data.status !== 'completed',
    });
  };

  useEffect(() => {
    if (
      isSuccessForCreateConfig &&
      createConfigData?.data?.session_id &&
      createConfigData?.data?.audit_log_id &&
      !isGoBackClicked
    ) {
      setMlConfigIds(() => ({
        sessionId: createConfigData.data.session_id,
        auditLogId: createConfigData.data.audit_log_id,
      }));
      setIsPolling(true);
    }
  }, [isSuccessForCreateConfig, createConfigData, isGoBackClicked]);

  useEffect(() => {
    if (isErrorForCreateConfig) {
      showNotification({
        type: 'error',
        message: 'Unable to create ml config. Something went wrong',
      });
    }
  }, [isErrorForCreateConfig]);

  useEffect(() => {
    if (!isSuccessForFetchConfig || !fetchConfigData?.data?.column_config.length) return;

    const sourceMapList = fetchConfigData.data.column_config.reduce((entry, cfg) => {
      if (cfg?.merchant_source_id) {
        entry[cfg.merchant_source_id] = {
          id_cols: cfg.source_cols.id_cols || [],
          amount_cols: cfg.source_cols.amount_cols || [],
          date_cols: cfg.source_cols.date_cols || [],
          other_cols: cfg.source_cols.other_cols || [],
        };
      }
      return entry;
    }, {});
    setSourceColumnList(sourceMapList);

    const getMaxLength = (key) =>
      fetchConfigData.data.column_config.reduce(
        (max, cfg) => Math.max(max, cfg[key]?.length || 0),
        0,
      );

    const maxLengths = {
      id: getMaxLength('id_matching_column_sets'),
      amount: getMaxLength('amount_matching_column_sets'),
      date: getMaxLength('date_matching_column_sets'),
      other: getMaxLength('other_matching_column_sets'),
    };

    const createMatchingColumnSet = (key, length) =>
      Array.from({ length }, (_, i) =>
        fetchConfigData.data.column_config.reduce((entry, cfg) => {
          if (cfg[key]?.[i]) {
            entry[cfg.merchant_source_id] = cfg[key][i];
          }
          return entry;
        }, {}),
      );

    const matchingColumnSets = {
      id_sets: createMatchingColumnSet('id_matching_column_sets', maxLengths.id),
      amount_sets: createMatchingColumnSet('amount_matching_column_sets', maxLengths.amount),
      date_sets: createMatchingColumnSet('date_matching_column_sets', maxLengths.date),
      other_sets: createMatchingColumnSet('other_matching_column_sets', maxLengths.other),
    };

    setTableData((prevData) => ({
      ...prevData,
      id_sets: matchingColumnSets.id_sets.length ? matchingColumnSets.id_sets : [{}],
      amount_sets: matchingColumnSets.amount_sets.length ? matchingColumnSets.amount_sets : [{}],
      date_sets: matchingColumnSets.date_sets.length ? matchingColumnSets.date_sets : [{}],
      other_sets: matchingColumnSets.other_sets.length ? matchingColumnSets.other_sets : [{}],
    }));

    if (
      (isSuccessForFetchConfig && fetchConfigData?.data?.status === 'ml_processed') ||
      (isSuccessForCreateConfig && fetchConfigData?.data?.status === 'completed')
    ) {
      setIsPolling(false);
    }
  }, [isSuccessForFetchConfig, fetchConfigData]);

  useEffect(() => {
    if (isSuccessForGenerateMlConfig && generateMlConfigData?.data?.audit_log_id) {
      setMlConfigIds((prevIds) => ({
        ...prevIds,
        auditLogId: generateMlConfigData.data.audit_log_id,
      }));
      setAiIngestionStages((prevStages) => ({
        ...prevStages,
        'Add Sources': true,
        Mapping: true,
      }));
    }
  }, [isSuccessForGenerateMlConfig, generateMlConfigData]);

  useEffect(() => {
    if (isErrorForGenerateMlConfig) {
      showNotification({
        type: 'error',
        message: 'Unable to generate ml config. Something went wrong',
      });
    }
  }, [isErrorForGenerateMlConfig]);

  // eslint-disable-next-line consistent-return
  useEffect(() => {
    if (isPolling) {
      const timer = setTimeout(() => setIsPolling(false), 2 * 60 * 1000);
      return () => clearTimeout(timer);
    }
  }, [isPolling]);

  return (
    <Box display="flex" flexDirection="column" gap="spacing.7" paddingY="spacing.6">
      <RenderErrorLoadingOrChild
        isLoading={isLoadingForCreateConfig}
        isError={isErrorForCreateConfig}
      >
        <Box
          display="flex"
          gap="spacing.4"
          flexDirection="row"
          justifyContent="space-between"
          alignItems="center"
        >
          <Box display="flex" gap="spacing.4" flexDirection="column">
            <Box display="flex" gap="spacing.3">
              <Heading weight="semibold" size="medium" color="surface.text.gray.normal">
                Source Mapping Rules
              </Heading>
              <Badge color="positive">AI Suggested</Badge>
            </Box>
            <Text variant="body" weight="medium" size="medium" color="surface.text.gray.muted">
              AI has automatically mapped the matching columns. You can still adjust the mapping by
              selecting the desired columns.
            </Text>
          </Box>
          <Button
            color="primary"
            variant="primary"
            size="medium"
            icon={ArrowRightIcon}
            iconPosition="right"
            isDisabled={isLoadingForFetchConfig || isPolling}
            isLoading={isLoadingForGenerateMlConfig}
            onClick={() => confirmSourcePredictedColumn({ tableData })}
          >
            Confirm
          </Button>
        </Box>
        <Box display="flex" flexDirection="column" gap="spacing.4">
          <RenderErrorLoadingOrChild
            isLoading={isLoadingForFetchConfig || isPolling}
            isError={isErrorForFetchConfig && !isPolling}
          >
            {COLUMN_SET_CONFIGS.map(({ title, tableName }) => (
              <ColumnMappingTable
                key={tableName}
                title={title}
                tableData={tableData[tableName]}
                columnConfig={fetchConfigData?.data?.column_config}
                sourceColumnList={sourceColumnList}
                tableName={tableName}
                onAddRow={addNewRow}
                onDeleteRow={deleteRow}
                onChangeColumn={handleOnChange}
              />
            ))}
          </RenderErrorLoadingOrChild>
        </Box>
      </RenderErrorLoadingOrChild>
    </Box>
  );
};

export default compose(connect(null, { showNotification }))(SourceMapping);
