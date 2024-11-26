import React, { useState, useEffect } from 'react';
import {
  Box,
  PlusIcon,
  Button,
  Table,
  Link as BladeLink,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableRow,
  TableCell,
  TableBody,
} from '@razorpay/blade/components';
import moment from 'moment';
import { useNavigate } from 'react-router-dom';

import { useSplitzService } from 'common/splitz';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { merchantFetch } from 'merchant/utils/ajax';
import { RenderErrorLoadingOrChild } from 'merchant/views/Reconciliations/commonComponents';
import { ReconScreens } from 'merchant/views/Reconciliations/const';
import { useReconTracking } from 'merchant/views/Reconciliations/hooks';
import { checkCustomReportingEnabled } from 'merchant/views/Reconciliations/utils';

import { DashboardTabs, RECON_CREATE_REPORT_URL, ProcessTabs } from './constants';
const cols = ['Name', 'Product', 'Type', 'Last Run', ''];

const Processes = () => {
  const { abExperiments } = useSplitzService();

  const [processList, setProcessList] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(false);
  const navigate = useNavigate();

  const fetchProcesses = async () => {
    try {
      setError(false);
      setIsLoading(true);
      const res = await merchantFetch({
        url: `recon-saas/recon_process`,
        mode: 'live',
        method: 'get',
      });
      if (res?.status_code === 200) {
        const { items } = res.data;
        setProcessList(items);
      } else {
        setError(true);
      }
    } catch {
      setError(true);
    } finally {
      setIsLoading(false);
    }
  };

  const createConfig = () => {
    analyticsTrackWithUserInfo({
      screen: ReconScreens.ProcessListing,
      objectName: 'recon new configuration',
      actionName: 'click',
    });
    navigate(`/reconciliations/create-config/2`);
  };

  const goToCreateReport = () => {
    navigate(RECON_CREATE_REPORT_URL);
  };

  const goToProcessListingPage = ({ processData }) => {
    analyticsTrackWithUserInfo({
      screen: ReconScreens.ProcessListing,
      objectName: 'recon process detail',
      actionName: 'click',
      properties: {
        activeProcessId: processData?.id,
        activeProcessName: processData?.name,
        aciveProcessType: processData?.type,
      },
    });
    navigate(
      `/reconciliations/dashboard/${DashboardTabs.PROCESSES}/${processData?.id}/${ProcessTabs.OVERVIEW}`,
    );
  };

  useReconTracking({
    objectName: 'recon process list',
    screen: ReconScreens.ProcessListing,
  });

  useEffect(() => {
    fetchProcesses();
  }, []);

  return (
    <Box testID="recon-process-listing">
      <Box display="flex" justifyContent="flex-end" marginBottom="spacing.4" gap="spacing.6">
        {checkCustomReportingEnabled({ abExperiments }) ? (
          <Button variant="secondary" onClick={goToCreateReport}>
            Create Report
          </Button>
        ) : null}
        <Button variant="primary" icon={PlusIcon} onClick={createConfig}>
          New Process
        </Button>
      </Box>
      <RenderErrorLoadingOrChild isError={error} isLoading={isLoading}>
        <Table data={{ nodes: processList }}>
          {(tableData) => (
            <>
              <TableHeader>
                <TableHeaderRow>
                  {cols.map((column) => (
                    <TableHeaderCell key={column}>{column}</TableHeaderCell>
                  ))}
                </TableHeaderRow>
              </TableHeader>
              <TableBody>
                {tableData.map((item, index) => (
                  <TableRow key={index} item={item}>
                    <TableCell>{item?.name}</TableCell>
                    <TableCell>{item?.product_name || item?.product_id}</TableCell>
                    <TableCell>{item?.type}</TableCell>
                    <TableCell>
                      {item?.last_run === 0 ? 'N.A' : moment(item?.last_run * 1000).format('lll')}
                    </TableCell>
                    <TableCell>
                      <BladeLink onClick={() => goToProcessListingPage({ processData: item })}>
                        Details
                      </BladeLink>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </>
          )}
        </Table>
      </RenderErrorLoadingOrChild>
    </Box>
  );
};
export default Processes;
