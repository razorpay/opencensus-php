import React, { useEffect, useState } from 'react';
import moment from 'moment';
import AsyncButton from 'react-async-button';
import GenericTooltip from 'common/ui/Tooltip';
import Time from 'common/ui/Time';

const TabRefreshButton = ({ timestamp, onRefresh, activeTab }) => {
  const [isRefreshDisabled, setRefreshDisabled] = useState(true); // Refresh icon has to be enabled after 5 min.

  const timerCallBack = (date, id) => {
    if (id === activeTab) {
      const diff = moment().diff(date, 'minutes');
      setRefreshDisabled(diff < 5);
    }
  };

  useEffect(() => {
    const diffInSec = timestamp ? moment().diff(moment.unix(timestamp), 'minutes') : 0;
    setRefreshDisabled(diffInSec < 5);
  }, [timestamp]);

  return (
    <div className="tab-refresh" data-testid="sr-tab-refresh">
      <span className="last-updated-at">
        Last updated:&nbsp;
        {timestamp ? (
          <Time
            key={activeTab}
            id={activeTab}
            value={timestamp}
            relative
            timerCallBack={timerCallBack}
          />
        ) : (
          '-- ago'
        )}
      </span>
      <AsyncButton
        text="Refresh"
        pendingText="Refreshing"
        className="btn btn-sm btn-text tab-refresh__btn"
        onClick={onRefresh}
        disabled={isRefreshDisabled}
      >
        {({ buttonText }) => (
          <span>
            {isRefreshDisabled ? (
              <GenericTooltip align="top">
                The page can be refreshed only after 5 minutes since the last refresh action.
              </GenericTooltip>
            ) : null}
            <span>{buttonText}</span>
            <i className="i i-refresh tab-refresh__btn-icon" />
          </span>
        )}
      </AsyncButton>
    </div>
  );
};

export default React.memo(TabRefreshButton);
