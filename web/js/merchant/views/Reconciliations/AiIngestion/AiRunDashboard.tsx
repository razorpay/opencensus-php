import React, { useEffect, useState } from 'react';
import {
  Box,
  Heading,
  Text,
  Alert,
  Divider,
  Button,
  Link,
  DownloadIcon,
} from '@razorpay/blade/components';
import { useMutation, useQuery } from '@tanstack/react-query';
import moment from 'moment';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { compose } from 'redux';

import ReconStatsCard from 'merchant/views/Reconciliations/AiIngestion/ReconStatsCard';
import {
  DashboardTabs,
  RECON_DASHBOARD_BASEURL,
} from 'merchant/views/Reconciliations/Dashboard/constants';
import {
  getMlReconStats,
  saveMlGeneratedProcess,
  downloadReconReport,
} from 'merchant/views/Reconciliations/api';
import { RenderErrorLoadingOrChild } from 'merchant/views/Reconciliations/commonComponents';
import { showNotification } from 'merchant_common/reducers/notifications';
import ColumnPreviewTable from 'merchant/views/Reconciliations/AiIngestion/ColumnPreviewTable';

import type { AiRunDashboardProps } from 'merchant/views/Reconciliations/AiIngestion/types';

const AiRunDashboard: React.FC<AiRunDashboardProps> = ({
  mlConfigIds,
  setIsGoBackClicked,
  setAiIngestionStages,
}) => {
  const navigate = useNavigate();

  const [isPolling, setIsPolling] = useState(true);

  const [tableData, setTableData] = useState<Record<string, Array<any>>>({
    id_sets: [],
    amount_sets: [],
    date_sets: [],
    other_sets: [],
  });

  const {
    data: mlReconStatsData,
    isLoading: isLoadingForMlReconStats,
    isError: isErrorForMlReconStats,
    isSuccess: isSuccessForMlReconStats,
  } = useQuery({
    queryKey: ['ml recon stats', mlConfigIds.auditLogId],
    queryFn: () => getMlReconStats({ auditLogId: mlConfigIds.auditLogId }),
    refetchInterval: isPolling ? 10000 : false,
    enabled: !!mlConfigIds.auditLogId,
  });

  const {
    mutate: saveProcessMutation,
    isLoading: isLoadingForSaveProcess,
    isError: isErrorForSaveProcess,
    isSuccess: isSuccessForSaveProcess,
  } = useMutation({
    mutationFn: () => saveMlGeneratedProcess({ auditLogId: mlConfigIds.auditLogId }),
  });

  const {
    data: downloadReportData,
    mutate: downloadReportMutation,
    isError: isErrorForDownloadReport,
    isSuccess: isSuccessForDownloadReport,
  } = useMutation({
    mutationFn: ({ runId }: { runId: string }) => downloadReconReport({ runId }),
  });

  const goBack = () => {
    setIsGoBackClicked(true);
    setAiIngestionStages((prevStages) => ({
      ...prevStages,
      'Add Sources': true,
      Mapping: false,
      Processing: false,
    }));
  };

  useEffect(() => {
    if (isErrorForMlReconStats && isPolling === false) {
      showNotification({ type: 'error', message: 'ML Recon status fetching failed.' });
    }
  }, [isErrorForMlReconStats, isPolling]);

  useEffect(() => {
    if (!isSuccessForMlReconStats) return;

    const { column_config } = mlReconStatsData.data;

    const getMaxLength = (key) =>
      column_config.reduce((max, cfg) => Math.max(max, cfg[key]?.length || 0), 0);

    const maxLengths = {
      id: getMaxLength('id_matching_column_sets'),
      amount: getMaxLength('amount_matching_column_sets'),
      date: getMaxLength('date_matching_column_sets'),
      other: getMaxLength('other_matching_column_sets'),
    };

    const generateMatchingColumnSet = (key, maxLength) =>
      Array.from({ length: maxLength }, (_, i) =>
        column_config.reduce((entry, cfg) => {
          if (cfg[key]?.[i]) {
            entry[cfg.merchant_source_id] = cfg[key][i];
          }
          return entry;
        }, {}),
      );

    const tableDataUpdates = {
      id_sets: generateMatchingColumnSet('id_matching_column_sets', maxLengths.id),
      amount_sets: generateMatchingColumnSet('amount_matching_column_sets', maxLengths.amount),
      date_sets: generateMatchingColumnSet('date_matching_column_sets', maxLengths.date),
      other_sets: generateMatchingColumnSet('other_matching_column_sets', maxLengths.other),
    };

    setTableData((prevData) => ({
      ...prevData,
      id_sets: tableDataUpdates.id_sets.length ? tableDataUpdates.id_sets : [],
      amount_sets: tableDataUpdates.amount_sets.length ? tableDataUpdates.amount_sets : [],
      date_sets: tableDataUpdates.date_sets.length ? tableDataUpdates.date_sets : [],
      other_sets: tableDataUpdates.other_sets.length ? tableDataUpdates.other_sets : [],
    }));

    if (mlReconStatsData?.data?.status === 'completed') {
      setIsPolling(false);
      setAiIngestionStages((prevStages) => ({
        ...prevStages,
        'Add Sources': true,
        Mapping: true,
        Processing: true,
      }));
    }
  }, [isSuccessForMlReconStats, mlReconStatsData]);

  useEffect(() => {
    if (isSuccessForSaveProcess) {
      navigate(`${RECON_DASHBOARD_BASEURL}/${DashboardTabs.PROCESSES}`);
    }
  }, [isSuccessForSaveProcess]);

  useEffect(() => {
    if (isErrorForSaveProcess) {
      showNotification({ type: 'error', message: 'Unable to save process' });
    }
  }, [isErrorForSaveProcess]);

  // eslint-disable-next-line consistent-return
  useEffect(() => {
    if (isPolling) {
      const timer = setTimeout(() => setIsPolling(false), 5 * 60 * 1000);
      return () => clearTimeout(timer);
    }
  }, [isPolling]);

  useEffect(() => {
    if (isErrorForDownloadReport) {
      showNotification({
        type: 'error',
        message: 'Unable to download report. Please try again after some time',
      });
    }
  }, [isErrorForDownloadReport]);

  useEffect(() => {
    if (isSuccessForDownloadReport && downloadReportData?.data?.report_url) {
      window.open(downloadReportData.data.report_url, '_blank');
    }
  }, [isSuccessForDownloadReport, downloadReportData]);

  return (
    <Box display="flex" flexDirection="column" gap="spacing.7" paddingY="spacing.6">
      <Box>
        <Alert
          color="information"
          description="Your first reconciliation run may take a few moments as our AI is generating rules and running reconciliations."
          emphasis="subtle"
          isDismissible={false}
          isFullWidth={true}
        />
      </Box>
      <Box display="flex" flexWrap="wrap" gap="spacing.7" justifyContent="start">
        <ReconStatsCard
          title="Total Records"
          value={mlReconStatsData?.data?.total_records || 0}
          subtitle={mlReconStatsData?.data?.total_records || 0}
          isLoading={isLoadingForMlReconStats || isPolling}
        />
        <ReconStatsCard
          title="Reconciled"
          value={`${Number(mlReconStatsData?.data?.reconciled_percentage).toFixed(2)}%`}
          subtitle={`${
            mlReconStatsData?.data?.reconciled_records
              ? Number(mlReconStatsData?.data?.reconciled_records).toFixed(0)
              : 0
          } records`}
          isLoading={isLoadingForMlReconStats || isPolling}
        />
        <ReconStatsCard
          title="Unreconciled"
          value={`${Number(mlReconStatsData?.data?.unreconciled_percentage).toFixed(2)}%`}
          subtitle={`${
            mlReconStatsData?.data?.unreconciled_records
              ? Number(mlReconStatsData?.data?.unreconciled_records).toFixed(0)
              : 0
          } records`}
          isLoading={isLoadingForMlReconStats || isPolling}
        />
        <ReconStatsCard
          title="Total Duration"
          value={`${mlReconStatsData?.data?.recon_duration} seconds` || '0'}
          subtitle={
            mlReconStatsData?.data?.recon_started_at
              ? `${moment
                  .unix(mlReconStatsData?.data?.recon_started_at)
                  .format('DD-MMM-YYYY HH:mm:ss')}`
              : '0'
          }
          isLoading={isLoadingForMlReconStats || isPolling}
        />
      </Box>
      <Link
        isDisabled={
          mlReconStatsData?.data?.status !== 'completed' && !mlReconStatsData?.data?.run_id
        }
        onClick={(e) => {
          e.preventDefault();
          downloadReportMutation({ runId: mlReconStatsData?.data?.run_id });
        }}
        icon={DownloadIcon}
        variant="button"
      >
        Download Recon Report
      </Link>
      <Divider
        dividerStyle="dashed"
        thickness="thinner"
        variant="normal"
        orientation="horizontal"
      />
      <Box display="flex" gap="spacing.7" flexDirection="column">
        <Box display="flex" gap="spacing.4" flexDirection="column">
          <Heading weight="semibold" size="medium" color="surface.text.gray.normal">
            Source Mapping Rules
          </Heading>
          <Text variant="body" weight="medium" size="medium" color="surface.text.gray.muted">
            AI has automatically mapped the matching columns. You can still adjust the mapping by
            selecting the desired columns.
          </Text>
        </Box>
        <Box display="flex" flexDirection="column" gap="spacing.4">
          <RenderErrorLoadingOrChild
            isLoading={isLoadingForMlReconStats || isPolling}
            isError={isErrorForMlReconStats && !isPolling}
          >
            <ColumnPreviewTable
              title="Identifier Column Set"
              tableData={tableData.id_sets}
              columnConfig={mlReconStatsData?.data.column_config}
            />
            <ColumnPreviewTable
              title="Amount Column Set"
              tableData={tableData.amount_sets}
              columnConfig={mlReconStatsData?.data.column_config}
            />
            <ColumnPreviewTable
              title="Date Column Set"
              tableData={tableData.date_sets}
              columnConfig={mlReconStatsData?.data.column_config}
            />
            <ColumnPreviewTable
              title="Other Column Set"
              tableData={tableData.other_sets}
              columnConfig={mlReconStatsData?.data.column_config}
            />
          </RenderErrorLoadingOrChild>
        </Box>
        <Box display="flex" justifyContent="end" gap="spacing.3">
          <Button
            color="primary"
            variant="secondary"
            size="medium"
            iconPosition="left"
            onClick={() => goBack()}
            isDisabled={
              !mlConfigIds.auditLogId ||
              isLoadingForMlReconStats ||
              isPolling ||
              isErrorForMlReconStats ||
              mlReconStatsData.data.status !== 'completed'
            }
          >
            Go Back
          </Button>
          <Button
            color="primary"
            variant="primary"
            size="medium"
            iconPosition="right"
            onClick={() => saveProcessMutation()}
            isDisabled={
              !mlConfigIds.auditLogId ||
              isLoadingForMlReconStats ||
              isPolling ||
              isErrorForMlReconStats ||
              mlReconStatsData.data.status !== 'completed'
            }
            isLoading={isLoadingForSaveProcess}
          >
            Save Process
          </Button>
        </Box>
      </Box>
    </Box>
  );
};

export default compose(connect(null, { showNotification }))(AiRunDashboard);
