import React, { Fragment } from 'react';

import { titleCase } from 'common/utils/rzp-utils';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { WALLET_AUTO_DEBIT_KEY } from 'merchant/views/Navigator/constants';

const IGNORE_FIELDS = [
  'Payment Methods',
  'UPI Features',
  'Netbanking Features',
  'optimizer_seamless_disabled',
  WALLET_AUTO_DEBIT_KEY,
];

function APIDetails({ providerDetails, isPaytmAutoDebitEnabled, walletAutoDebit }) {
  const ignoreFields =
    !isPaytmAutoDebitEnabled || !walletAutoDebit
      ? IGNORE_FIELDS.concat('CLIENT_KEY', 'CLIENT_SECRET')
      : IGNORE_FIELDS;

  return (
    <div className="list-group details-row-container">
      <EntityDetailRow
        label="Production API Details"
        value={() => (
          <div className="provider-api-details">
            {providerDetails.map(([key, values], index) => {
              if (!ignoreFields.includes(key) && !key.includes('metadata')) {
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
