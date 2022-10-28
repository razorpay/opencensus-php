import React from 'react';
import { WarningSvg } from 'merchant/components/Home/GenericPanel';

const NoDataMessage = ({ title = '', subtitle = '' }) => {
  return (
    <div className="no-data">
      <p className="no-data__title">
        <WarningSvg /> {title}
      </p>
      {subtitle && <p className="no-data__subtitle">{subtitle}</p>}
    </div>
  );
};

export default NoDataMessage;
