import React, { useCallback, useEffect, useState } from 'react';
import { Box } from '@razorpay/blade/components';
import { useParams } from 'react-router-dom';

import { merchantFetch } from 'merchant/utils/ajax';
import RunsListTable from 'merchant/views/Reconciliations/Dashboard/RunsListTable';
import { RenderErrorLoadingOrChild } from 'merchant/views/Reconciliations/commonComponents';
import { ReconScreens } from 'merchant/views/Reconciliations/const';
import { useReconTracking } from 'merchant/views/Reconciliations/hooks';

export default function ProcessRunsList({ activeProcess }) {
  const [runsList, setRunsList] = useState([]);
  const [paginationData, setPaginationData] = useState({});
  const [currentPage, setCurrentPage] = useState(0);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(false);

  const { processId: activeProcessesId } = useParams();

  const fetchRuns = useCallback(
    async (props = {}) => {
      try {
        const raw = {
          filter: {
            merchant_process_id: [activeProcessesId],
          },
          sort_key: '',
          page: 1,
          offset: 0,
          page_size: 10,
          ...props,
        };
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
          if (props?.first_id) {
            setCurrentPage(currentPage - 1);
          } else if (props?.last_id) {
            setCurrentPage(currentPage + 1);
          }
        } else {
          setError(true);
        }
      } catch (error) {
        setError(true);
      } finally {
        setIsLoading(false);
      }
    },
    [activeProcessesId, currentPage],
  );

  useReconTracking({
    objectName: 'recon run listing',
    screen: ReconScreens.ProcessListing,
    properties: {
      activeProcessesId,
      activeProcessName: activeProcess?.name,
      activeProcessType: activeProcess?.type,
    },
  });

  useEffect(() => {
    fetchRuns();
  }, []);

  const handlePagination = (type) => {
    switch (type) {
      case 'prev':
        currentPage > 0 && fetchRuns({ first_id: paginationData.first_id });
        break;
      case 'next':
        paginationData?.has_more && fetchRuns({ last_id: paginationData.last_id });
        break;
      default:
    }
  };

  return (
    <Box testID="recon-process-runs">
      <RenderErrorLoadingOrChild isError={error} isLoading={isLoading}>
        <RunsListTable
          nodes={runsList}
          currentPage={currentPage}
          handlePagination={handlePagination}
          paginationData={paginationData}
          screen={ReconScreens.ProcessRunListing}
          activeProcess={activeProcess}
        />
      </RenderErrorLoadingOrChild>
    </Box>
  );
}
