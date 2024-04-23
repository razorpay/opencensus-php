import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';

import { merchantFetch } from 'merchant/utils/ajax';

import ReconDashboard from './Dashboard';
import { RenderErrorLoadingOrChild } from './commonComponents';

const Reconciliation = () => {
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(false);
  const navigate = useNavigate();

  const fetchProcesses = async () => {
    setIsLoading(true);
    try {
      setError(false);
      const res = await merchantFetch({
        url: `recon-saas/recon_process`,
        mode: 'live',
        method: 'get',
      });
      if (res?.status_code === 200) {
        const { items } = res.data;
        if (items.length === 0) {
          navigate('/reconciliations/create-config/1');
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
    fetchProcesses();
  }, []);

  return (
    <RenderErrorLoadingOrChild isError={error} isLoading={isLoading}>
      <ReconDashboard />
    </RenderErrorLoadingOrChild>
  );
};

export default Reconciliation;
