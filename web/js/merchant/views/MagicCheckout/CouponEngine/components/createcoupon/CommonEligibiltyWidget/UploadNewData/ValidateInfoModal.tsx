import React from 'react';
import {
  SAMPLE_FILE_URL_FOR_EMAIL,
  SAMPLE_FILE_URL_FOR_MOBILE,
} from 'merchant/views/MagicCheckout/CouponEngine/constants';

export const ValidateModalInfo: React.FC = () => (
  <div className="modal-info">
    <h5 className="modal-info-heading">Note</h5>
    <ol className="validate-modal-ul">
      <li>
        File should follow the template format. Download{' '}
        <ol type="a">
          <li>
            For Mobile Number:{' '}
            <a className="btn-link" href={SAMPLE_FILE_URL_FOR_MOBILE}>
              <strong>Download Sample File</strong>
            </a>{' '}
          </li>
          <li>
            For Email id Number:{' '}
            <a className="btn-link" href={SAMPLE_FILE_URL_FOR_EMAIL}>
              <strong>Download Sample File</strong>
            </a>
          </li>
        </ol>
      </li>
      <li>CSV file should not have any header/column title</li>
      <li>Maximum acceptable rows in the csv file will be 1 million.</li>
    </ol>
  </div>
);
