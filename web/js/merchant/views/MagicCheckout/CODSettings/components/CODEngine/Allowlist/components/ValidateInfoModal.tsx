import React from 'react';

export const ValidateModalInfo = ({ sampleUrl }) => (
  <div className="modal-info" data-testid="order-history-validate-component">
    <h5 className="modal-info-heading">KEEP IN MIND</h5>
    <ol className="validate-modal-ul">
      <li>Save your file in CSV format.</li>
      <li>
        Upload pincodes as individual rows in column 1.
        <a className="btn-link" href={sampleUrl}>
          <strong> Sample CSV Attached.</strong>
        </a>
      </li>
      <li>Upload only valid pincodes; invalid pincodes will be discarded.</li>
      <li>Pincodes uploaded are compatible with basic COD settings only.</li>
    </ol>
  </div>
);
