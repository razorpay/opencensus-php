import React, { useState } from 'react';
import { Box, Button, Divider, DownloadIcon, Heading, Text } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import {
  FetchReportListConfigsResponse,
  ReportConfig,
} from 'merchant/views/Reconciliations/Dashboard/types';
import moment from 'moment';
import { useNavigate } from 'react-router-dom';

import ErrorBoundary, { Teams } from 'common/new-ui/ErrorBoundary';
import DownloadReportModal from 'merchant/views/Reconciliations/Dashboard/DownloadReportModal';
import { RECON_CREATE_REPORT_URL } from 'merchant/views/Reconciliations/Dashboard/constants';
import { fetchReportList } from 'merchant/views/Reconciliations/api';
import { RenderErrorLoadingOrChild } from 'merchant/views/Reconciliations/commonComponents';

const ReportList: React.FC = () => {
  const [isOpenDownloadModal, setIsOpenDownloadModal] = useState<boolean>(false);
  const [selectedDownloadReportConfig, setSelectedDownloadReportConfig] =
    useState<ReportConfig | null>(null);
  const navigate = useNavigate();

  const {
    data: reportListConfig,
    isLoading,
    isError,
  } = useQuery<FetchReportListConfigsResponse>({
    queryKey: ['reportList'],
    queryFn: fetchReportList,
  });

  const goToCreateReport = () => {
    navigate(RECON_CREATE_REPORT_URL);
  };

  const goToEditReport = ({ configId }: { configId: string }) => {
    navigate(`${RECON_CREATE_REPORT_URL}/${configId}`);
  };

  return (
    <ErrorBoundary team={Teams.RECON_SAAS} resetOnProps>
      <Box paddingY="spacing.6" display="flex" flexDirection="column" gap="spacing.5">
        <Box display="flex" flexDirection="column" gap="spacing.5">
          <Box display="flex" justifyContent="space-between" alignItems="baseline">
            <Heading>Configs</Heading>
            <Button variant="secondary" onClick={goToCreateReport}>
              Create New
            </Button>
          </Box>
          <RenderErrorLoadingOrChild isError={isError} isLoading={isLoading}>
            <Box display="grid" gap="spacing.5" gridTemplateColumns="repeat(2, 1fr)">
              {reportListConfig && reportListConfig.data.length > 0 ? (
                reportListConfig.data.map((report) => (
                  <Box
                    borderWidth="thin"
                    borderColor="surface.border.gray.muted"
                    borderRadius="medium"
                    padding="spacing.7"
                    key={report.id}
                  >
                    <Box display="flex" flexDirection="column" gap="spacing.4">
                      <Box display="flex" justifyContent="space-between" gap="spacing.2">
                        <Box display="flex" flexDirection="column" gap="spacing.1" width="90%">
                          <Text weight="semibold" color="surface.text.gray.normal">
                            {report.name}
                          </Text>
                          <Text
                            size="small"
                            color="surface.text.gray.subtle"
                            truncateAfterLines={1}
                          >
                            Processes:{' '}
                            {report.merchant_processes.length > 0
                              ? report.merchant_processes
                                  .map((process) => process.merchant_process_name)
                                  .join(', ')
                              : 'No processes'}
                          </Text>
                        </Box>
                        <Box display="flex" justifyContent="flex-end" width="10%">
                          <Button
                            variant="tertiary"
                            icon={DownloadIcon}
                            onClick={() => {
                              setIsOpenDownloadModal(true);
                              setSelectedDownloadReportConfig(report);
                            }}
                            aria-label={`Download report for ${report.name}`}
                          />
                        </Box>
                      </Box>
                      <Divider />
                      <Box display="flex" justifyContent="space-between">
                        <Box display="flex" flexDirection="column" gap="spacing.1">
                          <Text weight="semibold" color="surface.text.gray.normal">
                            {report.last_report_run_time
                              ? moment.unix(report.last_report_run_time).format('DD/MM/YYYY')
                              : moment.unix(report.config_created_at).format('DD/MM/YYYY')}
                          </Text>
                          <Text size="small" color="surface.text.gray.subtle">
                            {report.last_report_run_time
                              ? 'Last Run Triggered At'
                              : report.config_created_at
                              ? 'Created At'
                              : ''}
                          </Text>
                        </Box>
                        <Box display="flex" gap="spacing.3">
                          <Button
                            variant="secondary"
                            size="medium"
                            onClick={() => goToEditReport({ configId: report.id })}
                          >
                            Edit
                          </Button>
                        </Box>
                      </Box>
                    </Box>
                  </Box>
                ))
              ) : (
                <Box display="flex" justifyContent="center" alignItems="center" width="100%">
                  <Text>No reports available</Text>
                </Box>
              )}
            </Box>
          </RenderErrorLoadingOrChild>
        </Box>
      </Box>

      <DownloadReportModal
        downloadReportConfig={selectedDownloadReportConfig}
        isOpenDownloadModal={isOpenDownloadModal}
        setIsOpenDownloadModal={setIsOpenDownloadModal}
      />
    </ErrorBoundary>
  );
};

export default ReportList;
