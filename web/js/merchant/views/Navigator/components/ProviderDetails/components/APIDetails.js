import React, { Fragment } from 'react';

import { titleCase } from 'common/utils/rzp-utils';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { WALLET_AUTO_DEBIT_KEY } from 'merchant/views/Navigator/constants';

const IGNORE_FIELDS = [
  'Payment Methods',
  'UPI Features',
  'optimizer_seamless_disabled',
  WALLET_AUTO_DEBIT_KEY,
];

function APIDetails({ providerDetails }) {
  return (
    <div className="list-group details-row-container">
      <EntityDetailRow
        label="Production API Details"
        value={() => (
          <div className="provider-api-details">
            {providerDetails.map(([key, values], index) => {
              if (!IGNORE_FIELDS.includes(key) && !key.includes('metadata')) {
                return (
                  <Fragment key={index}>
                    <div className="key-name">{titleCase(key)}</div>
                    <div className="key-value">{values || '**********'}</div>
                  </Fragment>
                );
              }

              return null;
            })}
          </div>
        )}
      />
    </div>
  );
}

export default APIDetails;
