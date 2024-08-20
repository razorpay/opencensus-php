import React from 'react';
import {
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableRow,
  TableCell,
  TableBody,
  Link as BladeLink,
  LoaderIcon,
  CheckIcon,
  Badge,
  DownloadIcon,
  Box,
  Text,
  Button,
  ArrowLeftIcon,
  ArrowRightIcon,
} from '@razorpay/blade/components';
import ReconciledIcon from 'assets/reconciliations/reconciled.svg';
import moment from 'moment';
import { useNavigate, useParams } from 'react-router-dom';

import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  DashboardTabs,
  ProcessTabs,
  FILE_WORKFLOW_KEY,
} from 'merchant/views/Reconciliations/Dashboard/constants';
import { ReconScreens } from 'merchant/views/Reconciliations/const';
const cols = ['Run ID', 'Process Name', 'Last Update', 'Run Completion', 'Summary', ''];
export default function RunsListTable({
  nodes,
  currentPage,
  handlePagination,
  paginationData,
  screen,
  activeProcess,
}) {
  const navigate = useNavigate();
  const { processId } = useParams();
  const downloadReport = async (id) => {
    analyticsTrackWithUserInfo({
      screen,
      objectName: 'recon download report',
      actionName: 'click',
      properties: {
        runId: id,
      },
    });
    const res = await merchantFetch({
      url: `recon-saas/file_detail/report/signed_url?${FILE_WORKFLOW_KEY}=${id}`,
      mode: 'live',
      method: 'GET',
    });
    if (res?.status_code === 200) {
      window.open(res?.data?.report_url, '_blank');
    }
  };
  const getPercentReconciled = (stats) => {
    const percent = Number(stats?.recon_output.Reconciled.percentage);
    return isNaN(percent) ? '' : `${Math.round(percent)}%`;
  };
  const goToRunDetailsPage = ({ runId }) => {
    analyticsTrackWithUserInfo({
      screen: processId ? ReconScreens.ProcessRunListing : ReconScreens.GlobalRunListing,
      objectName: 'recon run detail',
      actionName: 'click',
      properties: {
        runId,
        ...(processId && { activeProcessesId: processId }),
        ...(activeProcess?.name && { activeProcessName: activeProcess.name }),
        ...(activeProcess?.type && { activeProcessType: activeProcess.type }),
      },
    });
    navigate(
      processId
        ? `/reconciliations/dashboard/${DashboardTabs.PROCESSES}/${processId}/${ProcessTabs.RUNS}/${runId}`
        : `/reconciliations/dashboard/${DashboardTabs.RUNS}/${runId}`,
    );
  };
  return (
    <>
      <Table data={{ nodes }} gridTemplateColumns="1fr 1fr 1fr 1fr 1fr 1.5fr">
        {(tableData) => (
          <>
            <TableHeader>
              <TableHeaderRow>
                {cols.map((column, idx) => {
                  return <TableHeaderCell key={idx}>{column}</TableHeaderCell>;
                })}
              </TableHeaderRow>
            </TableHeader>
            <TableBody>
              {tableData.map((item, index) => {
                const isComplete = item?.status.toLowerCase() === 'completed';
                return (
                  <TableRow key={index} item={item}>
                    <TableCell>{item.id}</TableCell>
                    <TableCell>{item.process_name}</TableCell>
                    <TableCell>{moment(item.updated_at * 1000).format('lll')}</TableCell>
                    <TableCell>
                      <Badge
                        size="large"
                        color={isComplete ? 'positive' : 'primary'}
                        icon={isComplete ? CheckIcon : LoaderIcon}
                      >
                        {item.status}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      {isComplete ? (
                        <Box display="flex" gap="spacing.2" alignItems="center">
                          <img src={ReconciledIcon} alt="Reconciled" />
                          {getPercentReconciled(item?.stats)} Reconciled
                        </Box>
                      ) : null}
                    </TableCell>
                    <TableCell>
                      <Box display="flex" gap="spacing.6" alignItems="center">
                        {isComplete ? (
                          <BladeLink
                            onClick={() => downloadReport(item?.id)}
                            alignItems="center"
                            icon={DownloadIcon}
                          >
                            Download Report
                          </BladeLink>
                        ) : null}
                        <BladeLink onClick={() => goToRunDetailsPage({ runId: item.id })}>
                          Details
                        </BladeLink>
                      </Box>
                    </TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </>
        )}
      </Table>
      <Box display="flex" justifyContent="flex-end" alignItems="center" marginTop="spacing.4">
        <Text marginRight="spacing.4">Showing Page: {currentPage + 1}</Text>
        <Button
          icon={ArrowLeftIcon}
          marginRight="spacing.4"
          isDisabled={currentPage === 0}
          onClick={() => handlePagination('prev')}
        >
          Previous
        </Button>
        <Button
          icon={ArrowRightIcon}
          isDisabled={!paginationData?.has_more || nodes.length === 0}
          onClick={() => handlePagination('next')}
        >
          Next
        </Button>
      </Box>
    </>
  );
}
