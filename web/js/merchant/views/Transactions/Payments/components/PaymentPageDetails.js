import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { getPaymentPageDetailsById } from 'merchant/views/PaymentPages/PaymentPages/model';

export default function PaymentPageDetails({ payment }) {
  const [paymentPageDetails, updatePaymentPageDetails] = useState(null);

  useEffect(() => {
    getPaymentPageDetailsById(payment.order_id)
      .then(({ data }) => {
        if (data && data.payment_page) {
          updatePaymentPageDetails(data.payment_page);
        }
      })
      .catch(() => {
        // error should be handled here
      });
  }, []);

  if (!paymentPageDetails) {
    return null;
  }

  return (
    <EntityDetailRow label="Payment Page Title">
      <b>{paymentPageDetails.title}</b>
      <br />

      <Link to={`/paymentpages/${paymentPageDetails.id}/payments#paymentpages`}>
        {paymentPageDetails.id}
      </Link>
    </EntityDetailRow>
  );
}
