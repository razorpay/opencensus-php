import moment from 'moment';

import Banner from '@libs/web-nexus/common/ui/Banner';
import DataTable from '@libs/web-nexus/common/ui/Table/DataTable';
import { getURLQueryParams } from '@libs/shared-utils';

const dateFormat = 'DD MMM YYYY';

export default (props) => {
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
      {!!(hasNavigatedFrom === 'home' && hasFrom && hasTo) && (
        <Banner cta="View All" ctaUrl={`/${title}`}>
          <span>
            Showing {title} {title === 'payments' && 'including created and failed '}
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
      {hasNavigatedFrom === 'paymentpages' && title === 'payments' && (
        <Banner cta="Clear" ctaUrl={`/${title}`}>
          <span>
            Showing all {title} for Payment Page id: <b>{params.payment_link_id}</b>
          </span>
        </Banner>
      )}
      <DataTable {...props} />
    </div>
  );
};
