import React from 'react';
import StyledHeader from './StyledHeader';

const renderErrorDetails = ({ count, reason }, index) => (
  <div key={`${reason}_${index}`} className="col-md-4">
    <div className="r-grid__item">
      <p className="item-text">{count}</p>
      <p className="item-desc">{reason}</p>
    </div>
  </div>
);

const ReasonsPanel = ({ heading, data = [] }) => {
  return (
    <div className="r-panel">
      <StyledHeader text={heading} />
      <div className="row r-grid">{data?.map(renderErrorDetails)}</div>
    </div>
  );
};

export default ReasonsPanel;
