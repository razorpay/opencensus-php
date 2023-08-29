import React from 'react';

import Banner from 'common/ui/Banner';
import NoEntityResultsFound from 'common/ui/NoEntityResultsFound';
import Spinner from 'common/ui/Spinner';

export default function BatchDetails({ renderDetails, ...props }) {
  let { downloadReportText } = props;
  const { batch = {}, isLoading, onDownload } = props;
  const batchName =
    batch.name && batch.name.length > 24 ? `${batch.name.substr(0, 24)}...` : batch.name;

  if (typeof downloadReportText === 'function') {
    downloadReportText = downloadReportText(props);
  }

  return (
    <div className="content-wrapper content-sm txn-details batch-details">
      {isLoading ? (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      ) : undefined !== batchName ? (
        <div className="panel panel-default SliderPanel">
          <div className="panel-heading">
            <i className="i i-plan text-primary" /> <strong>{batchName || batch.id}</strong>
          </div>
          <div className="SliderPanel__Body">
            <div className="panel-body">
              {batch.status === 'processed' && (
                <Banner cta="Download Report" ctaOnClick={onDownload.bind(this, batch.id)}>
                  <span data-testid="download-report">
                    {downloadReportText || 'Download the report containing all data.'}
                  </span>
                </Banner>
              )}
              {renderDetails && renderDetails(props)}
            </div>
          </div>
        </div>
      ) : (
        <div className="content-wrapper content-sm txn-details">
          <NoEntityResultsFound error={<span>No results found for given id</span>} />
        </div>
      )}
    </div>
  );
}
