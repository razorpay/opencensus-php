import React, { useCallback, useEffect, useState } from 'react';
import { Box } from '@razorpay/blade/components';
import { useMutation } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { useParams } from 'react-router-dom';
import { compose } from 'redux';

import { merchantFetch } from 'merchant/utils/ajax';
import RunsListTable from 'merchant/views/Reconciliations/Dashboard/RunsListTable';
import { deleteReconRun } from 'merchant/views/Reconciliations/api';
import { RenderErrorLoadingOrChild } from 'merchant/views/Reconciliations/commonComponents';
import { ReconScreens } from 'merchant/views/Reconciliations/const';
import { useReconTracking } from 'merchant/views/Reconciliations/hooks';
import { showNotification } from 'merchant_common/reducers/notifications';

const ReconRunsList = ({ activeProcess, showNotification }) => {
  const [runsList, setRunsList] = useState([]);
  const [paginationData, setPaginationData] = useState({});
  const [currentPage, setCurrentPage] = useState(0);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(false);

  const { processId: activeProcessesId } = useParams();
  const isProcessSpecific = Boolean(activeProcess);

  const {
    mutate: deleteReconRunMutate,
    isLoading: isLoadingForDeleteReconRun,
    isSuccess: isSuccessForDeleteReconRun,
    isError: isErrorForDeleteReconRun,
  } = useMutation({
    mutationFn: ({ runId }) => deleteReconRun({ runId }),
  });

  const fetchRuns = useCallback(
    async (props = {}) => {
      setError(false);
      try {
        const raw = {
          filter: isProcessSpecific
            ? { merchant_process_id: [activeProcessesId] }
            : { product_id: [], type: [], status: [] },
          sort_key: '',
          page: 1,
          offset: 0,
          page_size: 10,
          ...props,
        };
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
    [isProcessSpecific, activeProcessesId, currentPage],
  );

  useReconTracking({
    objectName: isProcessSpecific ? 'recon run listing' : 'recon global runs list',
    screen: isProcessSpecific ? ReconScreens.ProcessListing : ReconScreens.GlobalRunListing,
    properties: isProcessSpecific
      ? {
          activeProcessesId,
          activeProcessName: activeProcess?.name,
          activeProcessType: activeProcess?.type,
        }
      : {},
  });

  useEffect(() => {
    if (isSuccessForDeleteReconRun) {
      showNotification({
        type: 'success',
        message: 'Recon run deleted successfully',
      });
      fetchRuns();
    }
  }, [isSuccessForDeleteReconRun]);

  useEffect(() => {
    if (isErrorForDeleteReconRun) {
      showNotification({
        type: 'error',
        message: 'Unable to delete the recon run',
      });
    }
  }, [isErrorForDeleteReconRun]);

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
    <Box testID={isProcessSpecific ? 'recon-process-runs' : 'recon-runs-listing'}>
      <RenderErrorLoadingOrChild isError={error} isLoading={isLoading}>
        <RunsListTable
          nodes={runsList}
          currentPage={currentPage}
          handlePagination={handlePagination}
          paginationData={paginationData}
          screen={
            isProcessSpecific ? ReconScreens.ProcessRunListing : ReconScreens.GlobalRunListing
          }
          activeProcess={activeProcess}
          isLoadingForDeleteReconRun={isLoadingForDeleteReconRun}
          deleteReconRunMutate={deleteReconRunMutate}
        />
      </RenderErrorLoadingOrChild>
    </Box>
  );
};

export default compose(
  connect(null, {
    showNotification,
  }),
)(ReconRunsList);
