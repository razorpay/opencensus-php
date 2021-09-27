import React, { useState, useCallback } from 'react';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export default ({ data, tooltipAlign }) => {
  const [showInfoltip, setshowInfotip] = useState(false);

  // method to handle the showcase of the tooltip on the user mouseOver
  const handleShowTooltip = useCallback(() => {
    if (!showInfoltip) {
      analyticsTrack({
        objectName: 'failures analysis hover',
        actionName: 'hovered',
        screen: 'transactions',
        properties: {
          categoryName: data?.title,
          experimentName: data?.experiment,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
    setshowInfotip((prevState) => !prevState);
  }, [data, showInfoltip]);
  return (
    <div className="gitst-info-card">
      {data && (
        <>
          <div className="gitst-info-card-title">
            <span>{data?.title}</span>
            <span
              className="info"
              onMouseEnter={handleShowTooltip}
              onMouseLeave={handleShowTooltip}
            >
              <i className="i i-info-circle text-fade">
                {showInfoltip && (
                  <div className={`info-content${tooltipAlign === 'right' ? ' right-align' : ''}`}>
                    <div>{data?.helpText}</div>
                  </div>
                )}
              </i>
            </span>
          </div>
          <div className="gitst-info-card-value">{data?.value}</div>
        </>
      )}
    </div>
  );
};
