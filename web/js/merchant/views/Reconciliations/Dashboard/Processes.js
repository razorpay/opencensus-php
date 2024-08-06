import React, { useState, useEffect } from 'react';
import {
  Box,
  PlusIcon,
  Button,
  Link,
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableRow,
  TableCell,
  TableBody,
} from '@razorpay/blade/components';
import moment from 'moment';
import { useNavigate } from 'react-router-dom';

import { merchantFetch } from 'merchant/utils/ajax';
import { RenderErrorLoadingOrChild } from 'merchant/views/Reconciliations/commonComponents';

const cols = ['Name', 'Product', 'Type', 'Last Run', ''];

const Processes = ({ openDetail }) => {
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
    navigate(`/reconciliations/create-config/2`);
  };

  useEffect(() => {
    fetchProcesses();
  }, []);
  return (
    <Box testID="recon-process-listing">
      <Box display="flex" justifyContent="flex-end" marginBottom="spacing.4">
        <Button variant="secondary" icon={PlusIcon} onClick={createConfig}>
          New Configuration
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
                      <Link onClick={() => openDetail(item)}>Details</Link>
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
