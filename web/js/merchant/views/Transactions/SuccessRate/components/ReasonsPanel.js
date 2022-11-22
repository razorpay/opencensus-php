import React, { useCallback } from 'react';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import GenericTooltip from 'common/ui/Tooltip';
import StyledHeader from './StyledHeader';
import NoDataMessage from './NoDataMessage';
import {
  getFormattedNumber,
  getNoDataTitle,
  getNoDataSubTitle,
  srErrorReport,
} from 'merchant/views/Transactions/SuccessRate/helper';
import { arrayObjToCsv } from 'common/utils/rzp-utils';
import fileDownload from 'common/utils/file-download';
import {
  trackSuccessRateEvents,
  downloadSRErrorReport,
} from 'merchant/views/Transactions/SuccessRate/trackEvents';
import { PAYMENT_METHOD_VS_CALLOUT_DISPLAY_TEXT } from 'merchant/views/Transactions/SuccessRate/constants';

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

const ReasonsPanel = ({ isLoading, title = '', heading = '', data = [], tab, panelData }) => {
  const noDataTitle = !tab?.data?.total
    ? getNoDataTitle(tab)
    : `There were no ${title?.toLowerCase()} payment failure reasons reported for ${
        PAYMENT_METHOD_VS_CALLOUT_DISPLAY_TEXT[tab?.name] || tab?.name
      } in the selected date range`;

  const handleDownload = useCallback(() => {
    const res = srErrorReport(panelData);
    const csvData = arrayObjToCsv(res);
    fileDownload(csvData, `${tab?.name}_Errors_Report.csv`);
    trackSuccessRateEvents(downloadSRErrorReport({ fileName: `${tab?.name}_Errors_Report.csv` }));
  }, [panelData, tab.name]);

  if (isLoading) return LoadingState;

  return (
    <div className="rp-panel">
      <div className="rp-panel--header">
        <StyledHeader text={heading} />
        <button
          className="btn btn-default download-btn"
          onClick={handleDownload}
          disabled={isLoading}
          type="button"
        >
          <i className="i i-download" />
          <GenericTooltip align="top">Download the error report</GenericTooltip>
        </button>
      </div>
      {data?.length > 0 ? (
        <div className="row rp-grid">{data.map(renderErrorDetails)}</div>
      ) : (
        <NoDataMessage
          title={noDataTitle}
          subtitle={!tab?.data?.total ? getNoDataSubTitle(tab) : ''}
        />
      )}
    </div>
  );
};

export default ReasonsPanel;
