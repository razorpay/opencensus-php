import { useState, useEffect } from 'react';

import { useSplitzService } from 'common/splitz';
import {
  isRiskDisabled,
  isRiskFoh,
} from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/Settlement/utils';
import { useFohTicket } from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/store';
import { fetchFohTicketData } from 'merchant/containers/Home/RTUX/MerchantOverview/utils';
import { isSettlementSOHBlockEnabled } from 'merchant/views/Settlements/components/utils';

const useFohTicketData = () => {
  const splitz = useSplitzService();
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
    if ((isRiskFoh() || isRiskDisabled()) && isSettlementSOHBlockEnabled(splitz)) {
      fetchTicketData();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return { isLoading };
};

export default useFohTicketData;
