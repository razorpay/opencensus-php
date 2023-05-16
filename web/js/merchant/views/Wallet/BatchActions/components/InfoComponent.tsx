import React from 'react';
import { OrderedList } from 'merchant/views/Wallet/BatchActions/styled';

interface InfoProps {
  sampleUrl?: string;
  points: string[];
}

export const InfoComponent = ({ sampleUrl, points = [] }: InfoProps): JSX.Element => (
  <div className="modal-info">
    <h5 className="modal-info-heading">KEEP IN MIND</h5>
    <OrderedList className="validate-modal-ul">
      {sampleUrl && (
        <li>
          File should follow the template format. Download{' '}
          <a className="btn-link" href={sampleUrl}>
            <strong>sample file</strong>
          </a>{' '}
          for the template.
        </li>
      )}
      {points.map((point, index) => (
        <li key={index}>{point}</li>
      ))}
    </OrderedList>
  </div>
);
