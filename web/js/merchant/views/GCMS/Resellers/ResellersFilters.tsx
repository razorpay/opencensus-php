import React, { useState } from 'react';

import { StyledFilterDiv } from 'merchant/views/GCMS/shared/StyledDiv';
import { RESELLERS_STATUS } from 'merchant/views/GCMS/shared/constants';

interface ResellersFilterProps {
  onSearch: ({ status, resellerName }) => void;
}

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
const ResellersFilter = ({ onSearch }: ResellersFilterProps) => {
  const [status, setStatus] = useState('all');
  const [resellerName, setResellerName] = useState('');

  const onClear = () => {
    setStatus('all');
    setResellerName('');
    onSearch({ status: 'all', resellerName: '' });
  };

  const handleStatusChange = (value) => {
    setStatus(value);
  };

  const handleResellerNameChange = (value) => {
    setResellerName(value);
  };

  const handleSearch = () => {
    onSearch({ status, resellerName });
  };

  return (
    <StyledFilterDiv>
      <div className={`gcms-orders-filter-group ${'all-time-filter-selected'}`}>
        <div className="list-filter-container ">
          <div className="form-group list-filter-item">
            <label>Reseller Name</label>
            <input
              name="merchant_name"
              className="form-control input-sm"
              data-testid="merchant_name"
              onChange={(e) => {
                handleResellerNameChange(e.target.value);
              }}
            />
          </div>
          <div className="form-group list-filter-item">
            <label>Status</label>
            <select
              name="status"
              className="form-control input-sm"
              onChange={(e) => handleStatusChange(e.target.value)}
              id="status-dropdown"
              defaultValue={status}
              data-testid="status"
            >
              {Object.values(RESELLERS_STATUS).map((status) => (
                <option
                  key={status.value}
                  value={status.value}
                  data-testid={`option-${status.value}`}
                >
                  {status.label}
                </option>
              ))}
            </select>
          </div>

          <div className="form-group list-filter-item btn-toolbar">
            <button className="btn btn-primary btn-sm" onClick={handleSearch}>
              Search
            </button>
            <button className="btn btn-sm btn-text" onClick={onClear}>
              Clear
            </button>
          </div>
        </div>
      </div>
    </StyledFilterDiv>
  );
};

export default ResellersFilter;
