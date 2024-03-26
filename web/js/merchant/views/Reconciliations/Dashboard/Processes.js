import React, { useState, useEffect } from 'react';
import {
  Box,
  SelectInput,
  Dropdown,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  PlusIcon,
  Button,
  Link,
} from '@razorpay/blade/components';
import moment from 'moment';
import { useNavigate } from 'react-router-dom';

import TableBody from 'common/ui/TableBody';
import { merchantFetch } from 'merchant/utils/ajax';
import { Loader } from 'merchant/views/Reconciliations/commonComponents';

const cols = ['Name', 'Product', 'Type', 'Last Run', ''];

const Processes = ({ openDetail }) => {
  const [processList, setProcessList] = useState([]);
  const [isLoading, setIsLoading] = useState(false);

  const navigate = useNavigate();
  const fetchProcesses = async () => {
    setIsLoading(true);
    const res = await merchantFetch({
      url: `recon-saas/recon_process`,
      mode: 'live',
      method: 'get',
    });
    const { items } = res.data;
    setProcessList(items);
    setIsLoading(false);
  };

  const createConfig = () => {
    navigate(`/reconciliations/create-config/2`);
  };

  useEffect(() => {
    fetchProcesses();
  }, []);
  return (
    <Box testID="recon-process-listing">
      <Box
        display="flex"
        alignItems="center"
        justifyContent="space-between"
        marginBottom="spacing.4"
      >
        <Box display="flex">
          <Dropdown marginRight="spacing.4">
            <SelectInput name="product" defaultValue="all" prefix="Product: " />
            <DropdownOverlay>
              <ActionList>
                <ActionListItem title="All" value="all" />
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
          <Dropdown>
            <SelectInput name="type" defaultValue="all" prefix="Type: " />
            <DropdownOverlay>
              <ActionList>
                <ActionListItem title="All" value="all" />
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        </Box>
        <Button variant="secondary" icon={PlusIcon} onClick={createConfig}>
          New Configuration
        </Button>
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
            <TableBody colSpan={4} rows={processList}>
              {processList.map((item, index) => (
                <tr key={index}>
                  <td>{item?.name}</td>
                  <td>{item?.product_name || item?.product_id}</td>
                  <td>{item?.type}</td>
                  <td>
                    {item?.last_run === 0 ? 'N.A' : moment(item?.last_run * 1000).format('ll')}
                  </td>
                  <td>
                    <Link onClick={() => openDetail(item)}>Details</Link>
                  </td>
                </tr>
              ))}
            </TableBody>
          </table>
        </div>
      )}
    </Box>
  );
};

export default Processes;
