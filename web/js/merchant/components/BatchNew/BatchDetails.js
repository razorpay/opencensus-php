import React, { Component } from 'react';

import Banner from 'rzp/ui/Banner';
import Spinner from 'rzp/ui/Spinner';

import { trackSeeAllLinks } from 'merchant/containers/BatchNew/ga';

export default function BatchDetails({ renderDetails, ...props }) {
  let { batch = {}, isLoading, onDownload } = props;
  let batchName =
    batch.name && batch.name.length > 24
      ? `${batch.name.substr(0, 24)}...`
      : batch.name;

  return (
    <div class="content-wrapper content-sm txn-details batch-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            <i class="i i-plan text-primary" />{' '}
            <strong>{batchName || batch.id}</strong>
          </div>
          <div class="SliderPanel__Body">
            <Banner
              cta="Download Report"
              ctaOnClick={onDownload.bind(this, batch.id)}
            >
              <span>
                Download the report containing all Payment Links data.
              </span>
            </Banner>
            <div class="panel-body">
              {renderDetails && renderDetails(props)}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
