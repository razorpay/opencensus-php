import { useState, useEffect } from 'react';

import {
  isRiskDisabled,
  isRiskFoh,
} from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/Settlement/utils';
import { useFohTicket } from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/store';
import { fetchFohTicketData } from 'merchant/containers/Home/RTUX/MerchantOverview/utils';

const useFohTicketData = () => {
  const [isLoading, setIsLoading] = useState(false);
  const { setFohTicketId, setFohTicketStatus } = useFohTicket();

  const fetchTicketData = async () => {
    setIsLoading(true);
    const fohTicketData = await fetchFohTicketData();
    if (fohTicketData) {
      setFohTicketId(fohTicketData.data?.ticket_id);
      setFohTicketStatus(fohTicketData.data?.status);
    }
    setIsLoading(false);
  };

  useEffect(() => {
    if (isRiskFoh() || isRiskDisabled()) {
      fetchTicketData();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return { isLoading };
};

export default useFohTicketData;
