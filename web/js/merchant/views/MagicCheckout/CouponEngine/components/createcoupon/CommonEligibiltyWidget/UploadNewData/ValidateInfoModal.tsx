import React from 'react';

interface ValidateModalInfoProps {
  sampleUrl: string;
}

export const ValidateModalInfo: React.FC<ValidateModalInfoProps> = ({ sampleUrl }) => (
  <div className="modal-info">
    <h5 className="modal-info-heading">Note</h5>
    <ol className="validate-modal-ul">
      <li>
        File should follow the template format. Download{' '}
        <a className="btn-link" href={sampleUrl}>
          <strong>sample file.</strong>
        </a>
      </li>
      <li>Maximum acceptable rows in the csv file will be 1 million.</li>
    </ol>
  </div>
);
