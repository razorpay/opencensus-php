import React from 'react';

function NoProviderFound() {
  return (
    <div className="content-wrapper content-sm txn-details optimizer-provider-detail">
      <div className="panel panel-default SliderPanel provider-detail-panel">
        <div className="panel-heading">
          <b>Provider</b>
        </div>
        <div className="SliderPanel__Body">
          <div className="panel-body">
            <p className="no-provider">No target provider found!</p>
          </div>
        </div>
      </div>
    </div>
  );
}

export default NoProviderFound;
