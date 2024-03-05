import React from 'react';
import moment from 'moment';

import { getURLQueryParams } from '@dashboard/shared-utils/rzp-utils';
import Banner from './Banner';
import DataTable from './Table/DataTable';

const dateFormat = 'DD MMM YYYY';

const EntityTable = (props) => {
  const params = getURLQueryParams(props.location.search);
  const hasNavigatedFrom = params.ref;
  const hasFrom = !!params.from;
  const hasTo = !!params.to;

  const from = moment(+params.from * 1e3);
  const to = moment(+params.to * 1e3);
  const isSameDate = from.isSame(to, 'day');
  const title = props.title && props.title.toLowerCase();

  return (
    <div>
      {hasNavigatedFrom === 'home' && hasFrom && hasTo ? (
        <Banner cta="View All" ctaUrl={`/${title}`}>
          <span>
            Showing {title} {title === 'payments' ? 'including created and failed ' : null}
            {isSameDate ? 'on ' : 'from '}
            <b>{from.format(dateFormat)}</b>
            {!isSameDate ? (
              <span>
                {' '}
                to <b>{to.format(dateFormat)}</b>
              </span>
            ) : null}
          </span>
        </Banner>
      ) : null}
      {hasNavigatedFrom === 'paymentpages' && title === 'payments' ? (
        <Banner cta="Clear" ctaUrl={`/${title}`}>
          <span>
            Showing all {title} for Payment Page id: <b>{params.payment_link_id}</b>
          </span>
        </Banner>
      ) : null}
      <DataTable {...props} />
    </div>
  );
};

export default EntityTable;
