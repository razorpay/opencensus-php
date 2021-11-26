import React from 'react';
import PropTypes from 'prop-types';
import trackIS from 'merchant/views/Settlements/InstantSettlements/ga';

const PayoutFilters = ({
  handleFilterClear,
  handleFilterApply,
  handlePayoutIdChange,
  handleStatusChange,
  payoutId,
  status,
}) => {
  return (
    <div className="flex form-group">
      <div className="list-filter-item">
        <label>Ondemand Payout ID</label>
        <input value={payoutId} onChange={handlePayoutIdChange} className="form-control input-sm" />
      </div>
      <div className="list-filter-item">
        <label>Status</label>
        <select
          value={status}
          onChange={handleStatusChange}
          className="form-control input-sm"
          onClick={() => trackIS.filterISStatusPayoutDetails()}
        >
          <option value="">All</option>
          <option value="created">Created</option>
          <option value="initiated">Initiated</option>
          <option value="processed">Processed</option>
          <option value="reversed">Reversed</option>
        </select>
      </div>
      <div className="list-filter-item btn-toolbar">
        <button className="btn btn-outline btn-sm" onClick={handleFilterApply}>
          Search
        </button>
        <button className="btn btn-sm btn-text" onClick={handleFilterClear}>
          Clear
        </button>
      </div>
    </div>
  );
};

PayoutFilters.propTypes = {
  handleFilterClear: PropTypes.func,
  handleFilterApply: PropTypes.func,
  handlePayoutIdChange: PropTypes.func,
  handleStatusChange: PropTypes.func,
};

export default PayoutFilters;
