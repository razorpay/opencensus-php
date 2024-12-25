import { useState } from 'react';
import PayoutList from 'merchant/views/Settlements/InstantSettlements/PayoutDetails/PayoutList';
import PayoutFilters from 'merchant/views/Settlements/InstantSettlements/PayoutDetails/PayoutFilters';
import PropTypes from 'prop-types';
import trackIS from 'merchant/views/Settlements/InstantSettlements/ga';
import { trackOnDemandPayoutSearch } from 'merchant/views/Settlements/trackEvents';

const PayoutDetailsContainer = ({ instantSettlement }) => {
  const [status, setStatus] = useState('');
  const [payoutId, setPayoutID] = useState('');

  const [items, setItems] = useState(instantSettlement.ondemand_payouts?.items || []);

  const handleFilterApply = () => {
    trackIS.clickCTAISSearchPayoutDetails();
    trackOnDemandPayoutSearch({
      status,
      payoutId,
    });
    const filteredItems = instantSettlement.ondemand_payouts.items.filter((item) => {
      if (status && item.status !== status) {
        return false;
      }
      if (payoutId && item.id !== payoutId) {
        return false;
      }
      return true;
    });

    setItems(filteredItems);
  };

  const handleStatusChange = (e) => {
    trackIS.filterISStatusPayoutDetails();
    setStatus(e.target.value);
  };

  const handlePayoutIdChange = (e) => {
    trackIS.searchISIdPayoutDetails();
    setPayoutID(e.target.value);
  };

  const handleFilterClear = () => {
    trackIS.clickCTAISClearPayoutDetails();
    setStatus('');
    setPayoutID('');
    setItems(instantSettlement.ondemand_payouts.items);
  };

  return (
    <>
      <PayoutFilters
        handleFilterClear={handleFilterClear}
        handleFilterApply={handleFilterApply}
        handleStatusChange={handleStatusChange}
        handlePayoutIdChange={handlePayoutIdChange}
        status={status}
        payoutId={payoutId}
      />
      <PayoutList items={items} />
    </>
  );
};

PayoutDetailsContainer.propTypes = {
  instantSettlement: PropTypes.object,
};

export default PayoutDetailsContainer;
