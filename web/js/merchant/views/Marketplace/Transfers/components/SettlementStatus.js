import React from 'react';
import { titleCase } from 'common/utils/rzp-utils';

export default React.memo(function SettlementStatus({ status }) {
  return (
    <span className={status === 'settled' ? 'text-success' : null}>
      {status ? titleCase(status) : 'Not Applicable'}
    </span>
  );
});
