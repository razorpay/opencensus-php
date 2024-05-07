import React, { useEffect, useState } from 'react';
import {
  Box,
  SelectInput,
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';

import { merchantFetch } from 'merchant/utils/ajax';
import RunsListTable from 'merchant/views/Reconciliations/Dashboard/RunsListTable';
import { RenderErrorLoadingOrChild } from 'merchant/views/Reconciliations/commonComponents';

export default function ProcessRunsDetail({ activeProcess, openRunDetail }) {
  const [runsList, setRunsList] = useState([]);
  const [paginationData, setPaginationData] = useState({});
  const [currentPage, setCurrentPage] = useState(0);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(false);

  const fetchRuns = async (props = {}) => {
    try {
      const raw = {
        filter: {
          merchant_process_id: [activeProcess?.id],
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
  };

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
        <RunsListTable
          nodes={runsList}
          ctaAction={openRunDetail}
          currentPage={currentPage}
          handlePagination={handlePagination}
          paginationData={paginationData}
        />
      </RenderErrorLoadingOrChild>
    </Box>
  );
}
