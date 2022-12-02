import CopyLink from 'merchant/components/CopyLink';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';
import { NavLink } from 'react-router-dom';

export const paymentLinkId = {
  title: 'Payment Link Id',
  value: (item) => (
    <NavLink to={`/paymentlinks/${item.id}`}>
      <code>{item.id}</code>
    </NavLink>
  ),
};

export const invoiceId = {
  title: 'Invoice Id',
  value: (item) => (
    <NavLink to={`/invoices/${item.id}`}>
      <code>{item.id}</code>
    </NavLink>
  ),
};

export const referenceId = {
  title: 'Reference Id',
  value: (item) => item.receipt,
};

export const receiptNumber = {
  title: 'Receipt No.',
  value: (item) => item.receipt,
};

export const paymentLink = (onCopy) => ({
  title: 'Payment Link',
  value: (item) => (
    <CopyLink
      onCopy={(text) => {
        onCopy({
          invoiceId: item.id,
          text,
        });
      }}
      url={item.short_url}
    />
  ),
});

export const invoiveStatus = {
  title: 'Status',
  value: (item) => <InvoiceStatusLabel status={item?.status ? item.status.toLowerCase() : null} />,
};

export const customer = {
  title: 'Customer',
  value: (item) => (
    <div>
      <span className="contact">{item?.customer_details?.contact}</span>
      <br />
      <span className="email">{item?.customer_details?.email}</span>
    </div>
  ),
};
