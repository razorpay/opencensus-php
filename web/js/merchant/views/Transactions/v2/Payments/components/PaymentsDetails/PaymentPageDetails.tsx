import React, { useEffect, useState } from 'react';
import { Divider, Text } from '@razorpay/blade/components';
import { Link } from 'react-router-dom';

import { noop } from 'common/utils/rzp-utils';
import { getPaymentPageDetailsById } from 'merchant/views/PaymentPages/PaymentPages/model';

import { DetailRow } from './PaymentDetailsSection';

export type PaymentPageDetailsType = {
  id: string;
  title: string;
};

export default function PaymentPageDetails({ id }: { id: string }) {
  const [paymentPageDetails, setPaymentPageDetails] = useState<PaymentPageDetailsType | null>(null);

  useEffect(() => {
    getPaymentPageDetailsById(id)
      .then(({ data }) => {
        if (data && data.payment_page) {
          setPaymentPageDetails(data.payment_page);
        }
      })
      .catch(noop);
  }, [id]);

  if (!paymentPageDetails) {
    return null;
  }

  return (
    <>
      <Divider dividerStyle="solid" thickness="thick" variant="muted" />
      <DetailRow
        label="Payment Page Title"
        value={
          <>
            <Text weight="semibold">{paymentPageDetails.title}</Text>
            <Link to={`/paymentpages/${paymentPageDetails.id}/payments#paymentpages`}>
              {paymentPageDetails.id}
            </Link>
          </>
        }
      />
    </>
  );
}
