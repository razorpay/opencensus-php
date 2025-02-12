import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';

export default ({ invoice, onBackNavClick }) => {
  let isNew = !invoice.id;
  let invoiceText = 'New Invoice';

  if (invoice.receipt) {
    invoiceText = `#${invoice.receipt}`;
  }

  if (invoice.id) {
    invoiceText = `#${invoice.id}`;
  }

  return (
    <ol className="breadcrumb breadcrumb__backNav">
      <li>
        <a className="breadcrumb__backNav--link btn" onClick={onBackNavClick}>
          <i className="i i-arrow-back" />
          <span>All Invoices</span>
        </a>
      </li>
      <li>
        <span className="breadcrumb__backNav--heading">
          <i className="i i-chevron-right" />
          {invoiceText}
        </span>
        {isNew ? (
          <span className="label label-muted">Draft</span>
        ) : (
          <InvoiceStatusLabel status={invoice.status} />
        )}
      </li>
    </ol>
  );
};
