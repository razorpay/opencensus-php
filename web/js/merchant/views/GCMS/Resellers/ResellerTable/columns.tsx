import { Text, Badge } from '@razorpay/blade/components';
import { NavLink } from 'react-router-dom';

import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import { getCurrencySymbol } from 'common/ui/Amount';
import { RESELLERS_STATUS } from 'merchant/views/GCMS/shared/constants';
import { trackResellerDetailsPageClicked } from 'merchant/views/GCMS/Resellers/events';

const merchantName = {
  title: 'Reseller Name',
  value: (item) => <Text>{item.merchant_name}</Text>,
};

const merchantId = {
  title: 'Reseller ID',
  value: (item) => (
    <NavLink
      key={item.merchant_id}
      to={`${item.merchant_id}`}
      state={{ prevPath: location?.pathname }}
      onClick={() => {
        trackResellerDetailsPageClicked({
          resellerId: item.merchant_id,
          resellerName: item.merchant_name,
        });
      }}
    >
      {item.merchant_id}
    </NavLink>
  ),
};

const eligiblePrograms = {
  title: 'Eligible Programs',
  value: (item) => <Text>{item.eligible_programs}</Text>,
};
const orderCount = {
  title: 'Order Count',
  value: (item) => <Text>{item.order_count}</Text>,
};
const aggregateOrderValue = {
  title: 'Order Value',
  value: (item) => (
    <Text weight="semibold">
      <Text size="small" display="inline-flex" weight="regular">
        {getCurrencySymbol()}
      </Text>{' '}
      {getFormattedAmountNew(item.aggregate_order_value || 0)}
    </Text>
  ),
};

const status = {
  title: 'Status',
  value: (item) => (
    <Badge color={RESELLERS_STATUS[item.status].color}>{RESELLERS_STATUS[item.status].label}</Badge>
  ),
};

const resellerId = {
  title: 'Reseller ID',
  value: (item) => (
    <NavLink
      key={item.merchant_id}
      to={`/gcms/resellers/${item.merchant_id}`}
      state={{ prevPath: location?.pathname }}
      onClick={() => {
        trackResellerDetailsPageClicked({
          resellerId: item.merchant_id,
          resellerName: item.merchant_name,
        });
      }}
    >
      {item.merchant_id}
    </NavLink>
  ),
};

const resellerName = {
  title: 'Reseller Name',
  value: (item) => <Text>{item.merchant_name}</Text>,
};

export const resellerListColumns = [
  merchantId,
  merchantName,
  eligiblePrograms,
  orderCount,
  aggregateOrderValue,
  status,
];

export const unmappedResellerListColumns = [resellerId, resellerName, orderCount, status];
