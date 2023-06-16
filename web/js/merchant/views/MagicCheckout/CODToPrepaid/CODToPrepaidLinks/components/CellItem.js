import { Link } from 'react-router-dom';

import ActionComponent from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/components/ActionComponent';

import { PL_STATUSES_MAPPING } from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/constants';

export const paymentLinkAction = (onReview) => ({
  title: 'Actions',
  value: (item) =>
    item?.magic_payment_link?.status === 'sent' ? (
      <ActionComponent item={item} onReview={onReview} />
    ) : (
      '--'
    ),
});

export const paymentLinkStatus = {
  title: 'Conversion status',
  value: (item) => {
    const { status } = item?.magic_payment_link;
    return status ? (
      <span className={`status-label ${status}`}>{PL_STATUSES_MAPPING[status]}</span>
    ) : (
      '-'
    );
  },
  columnClass: 'payment-status-col',
};

export const paymentLinkOrderId = {
  title: 'Razorpay Order Id',
  value: (item) => (
    <Link to={`?order_id=${item.id}`} data-testid="order-info-id">
      <p>{item.id}</p>
    </Link>
  ),
};
