import React from 'react';

const NoDataMessage = ({ title = '', subtitle = '' }) => {
  return (
    <div className="no-data">
      <p className="no-data__title">{title}</p>
      {subtitle && <p className="no-data__subtitle">{subtitle}</p>}
    </div>
  );
};

export default NoDataMessage;
