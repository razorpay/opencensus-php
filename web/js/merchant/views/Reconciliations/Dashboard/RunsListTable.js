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
  Tooltip,
  ArrowLeftIcon,
  ArrowRightIcon,
  IconButton,
  TrashIcon,
} from '@razorpay/blade/components';
import ReconciledIcon from 'assets/reconciliations/reconciled.svg';
import moment from 'moment';
import { useNavigate, useParams, useLocation } from 'react-router-dom';

import { useSplitzService } from 'common/splitz';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  DashboardTabs,
  ProcessTabs,
  FILE_WORKFLOW_KEY,
  RECON_DASHBOARD_BASEURL,
} from 'merchant/views/Reconciliations/Dashboard/constants';
import { ReconScreens } from 'merchant/views/Reconciliations/const';
import { checkAllowedMerchantToDeleteReconRun } from 'merchant/views/Reconciliations/utils';

const cols = ['Run ID', 'Process Name', 'Last Update', 'Run Completion', 'Summary', '', ''];

const RunsListTable = ({
  nodes,
  currentPage,
  handlePagination,
  paginationData,
  screen,
  activeProcess,
  isLoadingForDeleteReconRun,
  deleteReconRunMutate,
}) => {
  const { abExperiments } = useSplitzService();
  const navigate = useNavigate();
  const { processId } = useParams();
  const location = useLocation();

  const isAllowedToDeleteReconRun = checkAllowedMerchantToDeleteReconRun({ abExperiments });

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
        ? `${RECON_DASHBOARD_BASEURL}/${DashboardTabs.PROCESSES}/${processId}/${ProcessTabs.RUNS}/${runId}`
        : `${RECON_DASHBOARD_BASEURL}/${DashboardTabs.RUNS}/${runId}`,
    );
  };

  const goToSplitScreen = ({ runId }) => {
    analyticsTrackWithUserInfo({
      screen: ReconScreens.GlobalRunListing,
      objectName: 'recon split screen',
      actionName: 'click',
    });
    navigate(
      `${RECON_DASHBOARD_BASEURL}/${DashboardTabs.PROCESSES}/${processId}/${ProcessTabs.SPLIT}/${runId}`,
    );
  };

  const isSplitScreenEnabled = /\/split-screen/i.test(location.pathname);

  return (
    <Box marginTop="spacing.3">
      <Table
        data={{ nodes }}
        gridTemplateColumns={
          isAllowedToDeleteReconRun
            ? '1fr 1fr 1fr 1fr 1fr 1.5fr 1fr 0.1fr'
            : '1fr 1fr 1fr 1fr 1fr 1.5fr 1fr'
        }
      >
        {(tableData) => (
          <>
            <TableHeader>
              <TableHeaderRow>
                {cols.map((column, idx) => {
                  return <TableHeaderCell key={idx}>{column}</TableHeaderCell>;
                })}
                {isAllowedToDeleteReconRun ? <TableHeaderCell /> : null}
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
                      <BladeLink
                        onClick={() => downloadReport(item?.id)}
                        alignItems="center"
                        icon={DownloadIcon}
                        isDisabled={!isComplete}
                      >
                        Download Report
                      </BladeLink>
                    </TableCell>
                    <TableCell>
                      <BladeLink
                        onClick={() => isSplitScreenEnabled ? 
                          goToSplitScreen({ runId: item.id }) : 
                          goToRunDetailsPage({ runId: item.id })
                        }
                      >
                        Details
                      </BladeLink>
                    </TableCell>
                    {isAllowedToDeleteReconRun ? (
                      <TableCell>
                        <Box display="flex" gap="spacing.6" alignItems="center">
                          <Tooltip content="This will delete the recon run" placement="bottom">
                            <IconButton
                              icon={() => (
                                <TrashIcon
                                  color={
                                    !isComplete || isLoadingForDeleteReconRun
                                      ? 'interactive.icon.negative.disabled'
                                      : 'interactive.icon.negative.subtle'
                                  }
                                />
                              )}
                              isDisabled={!isComplete || isLoadingForDeleteReconRun}
                              onClick={() => deleteReconRunMutate({ runId: item.id })}
                            />
                          </Tooltip>
                        </Box>
                      </TableCell>
                    ) : null}
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
    </Box>
  );
};

export default RunsListTable;
