import React from 'react';

import { SAMPLE_FILE_URL } from 'merchant/views/MagicCheckout/ShippingSettings/constants';

const ValidateModalInfo = (): JSX.Element => {
  return (
    <div className="modal-info">
      <h5 className="modal-info-heading">KEEP IN MIND</h5>
      <ol className="validate-modal-ul">
        <li>
          Every shipping provider has a different import template. Magic follows a standard template
          for upload of delivery pincode data upload.
        </li>
        <li>
          Download{' '}
          <a className="btn-link" href={SAMPLE_FILE_URL}>
            <strong>this sample file</strong>
          </a>{' '}
          for the expected template.
        </li>
        <li>
          If a state having the same pincode has been configured in a different zone, we will pick
          up rates from both zone and show both shipping options as multiple shipping options.
        </li>
      </ol>
    </div>
  );
};

export default ValidateModalInfo;
