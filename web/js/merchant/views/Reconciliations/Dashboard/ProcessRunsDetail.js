import React, { useEffect, useState } from 'react';
import {
  Box,
  SelectInput,
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  LoaderIcon,
  CheckIcon,
  Badge,
  Link,
  DownloadIcon,
} from '@razorpay/blade/components';
import moment from 'moment';

import Pager from 'common/ui/Pager';
import TableBody from 'common/ui/TableBody';
import { merchantFetch } from 'merchant/utils/ajax';
import { FILE_WORKFLOW_KEY } from 'merchant/views/Reconciliations/Dashboard/constants';
import { RenderErrorLoadingOrChild } from 'merchant/views/Reconciliations/commonComponents';

const cols = ['Run ID', 'Run Completion', 'Status', ''];

export default function ProcessRunsDetail({ activeProcess, openRunDetail }) {
  const [runsList, setRunsList] = useState([]);
  const [paginationData, setPaginationData] = useState({});
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(false);
  const raw = {
    filter: {
      merchant_process_id: [activeProcess?.id],
    },
    sort_key: '',
    page: 0,
    offset: 0,
  };

  const fetchRuns = async () => {
    try {
      setError(false);
      setIsLoading(true);
      const res = await merchantFetch({
        url: `recon-saas/recon_run`,
        mode: 'live',
        method: 'POST',
        data: raw,
      });
      if (res?.status_code === 200) {
        const { items, ...pageData } = res.data;
        setRunsList(items);
        setPaginationData(pageData);
      } else {
        setError(true);
      }
    } catch (error) {
      setError(true);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchRuns();
  }, []);

  const downloadReport = async (id) => {
    const res = await merchantFetch({
      url: `recon-saas/file_detail/report/signed_url?${FILE_WORKFLOW_KEY}=${id}`,
      mode: 'live',
      method: 'GET',
    });
    if (res?.status_code === 200) {
      window.open(res?.data?.report_url, '_blank');
    }
  };

  return (
    <Box testID="recon-process-runs">
      <Box
        display="flex"
        alignItems="center"
        justifyContent="space-between"
        marginBottom="spacing.4"
      >
        <Dropdown>
          <SelectInput name="status" defaultValue="all" prefix="Status: " />
          <DropdownOverlay>
            <ActionList>
              <ActionListItem title="All" value="all" />
            </ActionList>
          </DropdownOverlay>
        </Dropdown>
      </Box>
      <RenderErrorLoadingOrChild isError={error} isLoading={isLoading}>
        <>
          <div className="table-responsive">
            <table className="table table-hover">
              <thead>
                <tr>
                  {cols.map((column, idx) => {
                    return (
                      <th
                        key={idx}
                        style={{ background: '#324664', color: '#fff' }}
                        colSpan={column === 'Reconciliation Overview' ? 3 : 1}
                      >
                        {column}
                      </th>
                    );
                  })}
                </tr>
              </thead>
              <TableBody colSpan={4} rows={runsList}>
                {runsList.map((item, index) => {
                  const isCompleted = item?.status.toLowerCase() === 'completed';
                  return (
                    <tr key={index}>
                      <td>{item?.id}</td>
                      <td>{moment(item?.updated_at * 1000).format('lll')}</td>
                      <td>
                        <Badge
                          size="large"
                          color={isCompleted ? 'positive' : 'primary'}
                          icon={isCompleted ? CheckIcon : LoaderIcon}
                        >
                          {item.status}
                        </Badge>
                      </td>
                      <td>
                        {isCompleted ? (
                          <Link onClick={() => downloadReport(item?.id)} marginRight="spacing.4">
                            <DownloadIcon
                              color="interactive.icon.primary.normal"
                              marginRight="spacing.2"
                            />
                            Download Report
                          </Link>
                        ) : null}
                        <Link onClick={() => openRunDetail(item?.id)}>Details</Link>
                      </td>
                    </tr>
                  );
                })}
              </TableBody>
            </table>
          </div>
          <Pager
            count={paginationData.count}
            skip={paginationData.skip}
            length={paginationData.total_count}
            onClick={fetchRuns}
          />
        </>
      </RenderErrorLoadingOrChild>
    </Box>
  );
}
