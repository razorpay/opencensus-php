import InvoiceStatus from 'merchant/components/Invoices/InvoiceStatus'

export default ({ invoice }) => {
  let isNew = !invoice.id
  return (
    <ol class='breadcrumb breadcrumb__backNav'>
      <li>
        <a href='#/app/invoices/list' class='breadcrumb__backNav--link'>
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
            <InvoiceStatus status={invoice.status} />
        }
      </li>
    </ol>
  )
}
