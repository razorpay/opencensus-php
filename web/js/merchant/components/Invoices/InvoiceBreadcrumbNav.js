import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';

export default ({ invoice, onBackNavClick }) => {
  let isNew = !invoice.id;
  return (
    <ol class="custom-breadcrumb breadcrumb breadcrumb__backNav">
      <li>
        <a class="breadcrumb__backNav--link btn" onClick={onBackNavClick}>
          <i class="i i-arrow-back" />
          <span>All Invoices</span>
        </a>
      </li>
      <li>
        <h3 class="breadcrumb__backNav--heading">
          {invoice.receipt || invoice.id || 'New Invoice'}
        </h3>
        {isNew ? (
          <span class="label label-muted">Unsaved</span>
        ) : (
          <InvoiceStatusLabel status={invoice.status} />
        )}
      </li>
    </ol>
  );
};
