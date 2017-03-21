import { InvoiceStatusLabel } from 'merchant/components/StatusLabel'

export default ({ invoice, onBackNavClick }) => {
  let isNew = !invoice.id
  return (
    <ol class='breadcrumb breadcrumb__backNav'>
      <li>
        <a class='breadcrumb__backNav--link' onClick={onBackNavClick}>
          <i class='fa fa-arrow-left'></i>
          <span>All Invoices</span>
        </a>
      </li>
      <li>
        <h3 class='breadcrumb__backNav--heading'>
          { invoice.receipt || invoice.id || 'New Invoice' }
        </h3>
        {
          isNew ?
            <span class='label label-muted'>Unsaved</span> :
            <InvoiceStatusLabel status={invoice.status} />
        }
      </li>
    </ol>
  )
}
