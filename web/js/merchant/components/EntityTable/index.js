import moment from 'moment';

import Banner from 'rzp/ui/Banner';
import DataTable from 'rzp/ui/Table/DataTable';
import { getURLQueryParams } from 'rzp/utils/rzp-utils';

const dateFormat = 'DD MMM YYYY';

export default props => {
  const params = getURLQueryParams(props.location.search),
    hasNavigatedFrom = params.ref,
    hasFrom = !!params.from,
    hasTo = !!params.to;

  const from = moment(+params.from * 1e3),
    to = moment(+params.to * 1e3),
    isSameDate = from.isSame(to, 'day'),
    title = props.title && props.title.toLowerCase();

  return (
    <div>
      {!!(hasNavigatedFrom === 'home' && hasFrom && hasTo) && (
        <Banner cta="View All" ctaUrl={`/${title}`}>
          <span>
            Showing {title}{' '}
            {title === 'payments' && 'including created and failed '}
            {isSameDate ? 'on ' : 'from '}
            <b>{from.format(dateFormat)}</b>
            {!isSameDate && (
              <span>
                {' '}
                to <b>{to.format(dateFormat)}</b>
              </span>
            )}
          </span>
        </Banner>
      )}
      {hasNavigatedFrom === 'paymentpages' &&
        title === 'payments' && (
          <Banner cta="Clear" ctaUrl={`/${title}`}>
            <span>
              Showing all {title} for Payment Page id:{' '}
              <b>{params.payment_link_id}</b>
            </span>
          </Banner>
        )}
      <DataTable {...props} />
    </div>
  );
};
