import { CustomerDetailsWrapper } from 'merchant/views/MagicCheckout/CODOrdersTab/common/styled';

const CustomerDetails = ({ item }) => {
  const { name, line1, line2, city, state, landmark, contact, zipcode } =
    item.customer_details?.shipping_address || {};
  const {
    name: billingName,
    line1: billingAddressLine1,
    line2: billingAddressLine2,
    city: billingAddressCity,
    state: billingAddressState,
    landmark: billingAddressLandmark,
    contact: billingAddressContact,
    zipcode: billingAddressZipcode,
  } = item.customer_details?.billing_address || {};

  return (
    <CustomerDetailsWrapper>
      <div className="customer-details">
        <div className="row contact-details">
          <div className="col-sm-6 column-title">Contact</div>
          <div className="col-sm-6 column-value">
            <div className="info-text">{item.customer_details.contact ?? '-'}</div>
            <div className="info-text">{item.customer_details.email ?? '-'}</div>
          </div>
        </div>
        <div className="row shipping-details">
          <div className="col-sm-6 column-title">Shipping Address</div>
          <div className="col-sm-6 column-value">
            <div className="info-text">{name ?? '-'}</div>
            <div className="info-text">
              {line1 ?? '-'}, {line2 ?? '-'}, {city ?? '-'}, {state ?? '-'},{' '}
              {zipcode ? `-${zipcode}` : '-'}
            </div>
            {landmark ? <div>Landmark: {landmark ?? '-'}</div> : null}
            <div className="info-text">
              Phone Number:
              {` ${contact ?? '-'}`}
            </div>
          </div>
        </div>
        <div className="row billing-details">
          <div className="col-sm-6 column-title">Billing Address</div>
          <div className="col-sm-6 column-value">
            <div className="info-text">{billingName ?? '-'}</div>
            <div className="info-text">
              {billingAddressLine1 ?? '-'}, {billingAddressLine2 ?? '-'},{' '}
              {billingAddressCity ?? '-'}, {billingAddressState ?? '-'}
              {billingAddressZipcode ? `-${billingAddressZipcode}` : '-'}
            </div>
            {billingAddressLandmark ? (
              <div className="info-text">Landmark: {billingAddressLandmark ?? '-'}</div>
            ) : null}
            <div className="info-text">Phone Number: {billingAddressContact ?? '-'}</div>
          </div>
        </div>
      </div>
    </CustomerDetailsWrapper>
  );
};

export default CustomerDetails;
