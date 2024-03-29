import React, { useEffect, useState } from 'react';
import {
  Box,
  SelectInput,
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  Button,
  Link,
  LoaderIcon,
  CheckIcon,
  Badge,
  Text,
  ArrowLeftIcon,
  ArrowRightIcon,
  DownloadIcon,
} from '@razorpay/blade/components';
import moment from 'moment';

import TableBody from 'common/ui/TableBody';
import { merchantFetch } from 'merchant/utils/ajax';
import { Loader } from 'merchant/views/Reconciliations/commonComponents';

const cols = ['Run ID', 'Process Name', 'Last Update', 'Run Completion', ''];

export default function Runs({ openDetail }) {
  const [runsList, setRunsList] = useState([]);
  const [paginationData, setPaginationData] = useState({});
  const [isLoading, setIsLoading] = useState(false);
  const [currentPage, setCurrentPage] = useState(0);

  const fetchRuns = async (props = {}) => {
    const raw = {
      filter: {
        product_id: [],
        type: [],
        status: [],
      },
      sort_key: '',
      page: 0,
      offset: 0,
      page_size: 20,
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
      setIsLoading(false);
      if (props?.first_id) {
        setCurrentPage(currentPage - 1);
      } else if (props?.last_id) {
        setCurrentPage(currentPage + 1);
      }
    }
  };

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

  useEffect(() => {
    fetchRuns();
  }, []);

  const downloadReport = async (id) => {
    const res = await merchantFetch({
      url: `recon-saas/file_detail/report/signed_url?file_detail_workflow_id=${id}`,
      mode: 'live',
      method: 'GET',
    });
    if (res?.status_code === 200) {
      window.open(res?.data?.report_url, '_blank');
    }
  };

  return (
    <Box testID="recon-runs-listing">
      <Box
        display="flex"
        alignItems="center"
        justifyContent="space-between"
        marginBottom="spacing.4"
      >
        <Box display="flex">
          <Dropdown marginRight="spacing.4">
            <SelectInput name="configurations" defaultValue="all" prefix="Configurations: " />
            <DropdownOverlay>
              <ActionList>
                <ActionListItem title="All" value="all" />
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
          <Dropdown>
            <SelectInput name="status" defaultValue="all" prefix="Status: " />
            <DropdownOverlay>
              <ActionList>
                <ActionListItem title="All" value="all" />
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        </Box>
      </Box>
      {isLoading ? (
        <Loader />
      ) : (
        <div className="table-responsive">
          <table className="table table-hover">
            <thead>
              <tr>
                {cols.map((column, idx) => {
                  return (
                    <th key={idx} style={{ background: '#324664', color: '#fff' }}>
                      {column}
                    </th>
                  );
                })}
              </tr>
            </thead>
            <TableBody colSpan={4} rows={runsList}>
              {runsList.map((item, index) => (
                <tr key={index}>
                  <td>{item.id}</td>
                  <td>{item.process_name}</td>
                  <td>{moment(item.updated_at * 1000).format('lll')}</td>
                  <td>
                    <Badge
                      size="large"
                      color={item.status.toLowerCase() === 'completed' ? 'positive' : 'primary'}
                      icon={item.status.toLowerCase() === 'completed' ? CheckIcon : LoaderIcon}
                    >
                      {item.status}
                    </Badge>
                  </td>
                  <td>
                    {item?.status.toLowerCase() === 'completed' ? (
                      <Link onClick={() => downloadReport(item?.id)} marginRight="spacing.4">
                        <DownloadIcon
                          color="interactive.icon.primary.normal"
                          marginRight="spacing.2"
                        />
                        Download Report
                      </Link>
                    ) : null}
                    <Link onClick={() => openDetail(item.id)}>Details</Link>
                  </td>
                </tr>
              ))}
            </TableBody>
          </table>
        </div>
      )}
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
          isDisabled={!paginationData?.has_more || runsList.length === 0}
          onClick={() => handlePagination('next')}
        >
          Next
        </Button>
      </Box>
    </Box>
  );
}
