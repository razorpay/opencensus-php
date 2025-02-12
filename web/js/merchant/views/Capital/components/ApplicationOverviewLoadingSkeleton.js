import React from 'react';
import CircularProgress from 'common/new-ui/CircularProgress';
import MultiLevelStepper from 'merchant/views/Capital/components/MultiLevelStepper';
import { CONSOLIDATED_STATES } from '../Loans/constants';

function ApplicationOverviewLoadingSkeleton() {
  return (
    <div className="status-overview">
      <div className="loan-application-overview-header flex">
        <div className="section">
          <h4 className="title no-margin PlaceholderLoader" />
          <p className="description text--secondary PlaceholderLoader" />
        </div>
        <div className="loan-application-progress-wrapper flex">
          <CircularProgress progress={0} size={22} showPercentage={false} />
          <div className="m-l progress-text-wrapper">
            <h4 className="no-margin PlaceholderLoader" />
          </div>
        </div>
      </div>
      <div>
        <MultiLevelStepper loading>
          {Object.keys(CONSOLIDATED_STATES).map(step => (
            <MultiLevelStepper.ParentStep status="" title="" description="" />
          ))}
        </MultiLevelStepper>
      </div>
    </div>
  );
}

export default ApplicationOverviewLoadingSkeleton;
