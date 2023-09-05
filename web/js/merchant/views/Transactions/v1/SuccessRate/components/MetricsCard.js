import React from 'react';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { getFixedNumber, classList } from 'common/utils/rzp-utils';
import OverviewGraph from './OverviewGraph';
import {
  METHOD_HELP_TEXT,
  PAYMENT_METHOD_VS_CALLOUT_DISPLAY_TEXT,
} from 'merchant/views/Transactions/v1/SuccessRate/constants';

const MetricsCard = ({ isLoading, metric, isActive }) => {
  const { title = '', sr = '', overviewHistogram = {}, total, name } = metric;
  const { datasets = [] } = overviewHistogram;
  const noData = !isLoading && datasets?.length === 0;

  const renderCardDetails = () => {
    if (isLoading) {
      return (
        <div>
          <PlaceholderLoader data-testid="metrics-card-loader" />
          <PlaceholderLoader className="placeholder-loader" />
        </div>
      );
    }
    return (
      <div aria-label="metric-card">
        <div className="metrics-card__title">
          <p className="display-text">{title}</p>
          {isActive && (
            <small className="help-content">
              <i className="i i-help-outline" />
              <Popover align="right">
                <PopoverBody>
                  <div>{METHOD_HELP_TEXT}</div>
                </PopoverBody>
              </Popover>
            </small>
          )}
        </div>
        {total ? (
          <h1>
            <span data-testid={`${name}-metric-card-sr-value`}>{`${getFixedNumber(
              sr || 0,
            )}%`}</span>
          </h1>
        ) : (
          <p className="callout-text">{`No payments were made via ${
            PAYMENT_METHOD_VS_CALLOUT_DISPLAY_TEXT[name] || title
          } in the selected date range`}</p>
        )}
      </div>
    );
  };

  return (
    <div className={classList('metrics-card', isActive && 'active')}>
      {renderCardDetails()}
      <div className={classList('mini-chart', noData && 'no-data', isActive && 'active')}>
        <div className="min-chart-content">
          {!isLoading && <OverviewGraph histogram={datasets} isActive={isActive} />}
        </div>
      </div>
    </div>
  );
};

export default MetricsCard;
