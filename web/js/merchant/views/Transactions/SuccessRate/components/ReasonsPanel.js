import React from 'react';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import StyledHeader from './StyledHeader';
import NoDataMessage from './NoDataMessage';
import { getFormattedNumber } from '../helper';

const LoadingState = (
  <div className="rp-panel">
    <PlaceholderLoader style={{ width: '60%' }} />
    <PlaceholderLoader />
  </div>
);

const renderErrorDetails = ({ count, reason }, index) => (
  <div key={index} className="col-md-4">
    <div className="rp-grid__item">
      <p className="item-count">{getFormattedNumber(count)}</p>
      <p className="item-description">{reason}</p>
    </div>
  </div>
);

const ReasonsPanel = ({ isLoading, title = '', heading = '', data = [] }) => {
  if (isLoading) return LoadingState;
  return (
    <div className="rp-panel">
      <StyledHeader text={heading} />
      {data?.length > 0 ? (
        <div className="row rp-grid">{data.slice(0, 5).map(renderErrorDetails)}</div>
      ) : (
        <NoDataMessage
          title={`No ${title.toLowerCase()} payment failures were reported in the selected duration`}
        />
      )}
    </div>
  );
};

export default ReasonsPanel;
