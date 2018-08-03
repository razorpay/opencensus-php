import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';

export default ({ invoice, onBackNavClick }) => {
  let isNew = !invoice.id;
  return (
    <ol class="breadcrumb breadcrumb__backNav">
      <li>
        <a class="breadcrumb__backNav--link btn" onClick={onBackNavClick}>
          <i class="i i-arrow-back" />
          <span>All Invoices</span>
        </a>
      </li>
      <li>
        <span class="breadcrumb__backNav--heading">
          <i class="i i-chevron-right" />
          {`#${invoice.receipt}` || `#${invoice.id}` || 'New Invoice'}
        </span>
        {isNew ? (
          <span class="label label-muted">Draft</span>
        ) : (
          <InvoiceStatusLabel status={invoice.status} />
        )}
      </li>
    </ol>
  );
};
