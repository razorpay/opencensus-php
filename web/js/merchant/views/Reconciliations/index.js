import React, { useEffect, useState } from 'react';
import { Box, Spinner } from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';

import { merchantFetch } from 'merchant/utils/ajax';

import ReconDashboard from './Dashboard';

const Reconciliation = () => {
  const [isLoading, setIsLoading] = useState(true);
  const navigate = useNavigate();

  const fetchProcesses = async () => {
    setIsLoading(true);
    const res = await merchantFetch({
      url: `recon-saas/recon_process`,
      mode: 'live',
      method: 'get',
    });
    const { items } = res.data;
    if (items.length === 0) {
      navigate('/reconciliations/create-config/1');
    }
    setIsLoading(false);
  };

  useEffect(() => {
    fetchProcesses();
  }, []);

  return (
    <Box>
      {isLoading ? (
        <Box height="400px" display="flex" justifyContent="center" alignItems="center">
          <Spinner size="xlarge" />
        </Box>
      ) : (
        <ReconDashboard />
      )}
    </Box>
  );
};

export default Reconciliation;
