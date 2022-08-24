import React from 'react';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { getFixedNumber } from 'common/utils/rzp-utils';
import OverviewGraph from './OverviewGraph';

const MetricsCard = ({ isLoading, metric, isActive }) => {
  const { title = '', helpText = '', sr = '', overviewHistogram } = metric;
  const { datasets = [] } = overviewHistogram;
  const noData = !isLoading && datasets?.length === 0;

  return (
    <div className={`metrics-card ${isActive ? 'active' : ''}`}>
      {!isLoading ? (
        <p className="metrics-card__title">
          {title}
          {isActive && helpText && (
            <small className="help-content">
              <i class="i i-help-outline" />
              <Popover align="top">
                <PopoverBody>
                  <div>{helpText}</div>
                </PopoverBody>
              </Popover>
            </small>
          )}
        </p>
      ) : (
        <PlaceholderLoader />
      )}
      {!isLoading ? (
        <h1>
          <span>{sr ? `${getFixedNumber(sr)}%` : '--'}</span>
        </h1>
      ) : (
        <PlaceholderLoader style={{ width: '60%', height: '16px', margin: '16px 0' }} />
      )}
      <div className={`mini-chart ${noData ? 'no-data' : ''} ${isActive ? 'active' : ''}`}>
        <div className="min-chart-content">
          {!isLoading && <OverviewGraph histogram={datasets} isActive={isActive} />}
        </div>
      </div>
    </div>
  );
};

export default MetricsCard;
