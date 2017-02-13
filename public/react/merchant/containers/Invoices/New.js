import { Component, PropTypes } from 'react'
import { Field, FieldArray, reduxForm, formValueSelector } from 'redux-form'
import { connect } from 'react-redux'
import AsyncButton from 'react-async-button'
import Time from 'rzp/ui/Time'
import Alert from 'rzp/ui/Forms/Alert'
import InputField from 'rzp/ui/Forms/InputField'
import DatePickerField from 'rzp/ui/Forms/DatePickerField'
import AutoResizeTextarea from 'rzp/ui/Forms/AutoResizeTextarea'
import TypeAhead from 'rzp/ui/Select/TypeAhead'
import Spinner from 'rzp/ui/Spinner'
import InlineField from 'rzp/ui/Forms/InlineField'
import Clipboard from 'rzp/ui/Clipboard'
import { findBy } from 'rzp/utils/rzp-utils'

import LineItemTable from './LineItemTable'
import { fetchCustomersForAutocomplete } from 'merchant/modules/customers'
import { fetchItemsForAutocomplete } from 'merchant/modules/items'
import { saveInvoice, highLightInvoice, deleteInvoice } from 'merchant/modules/invoices/list'
import { fetchInvoice } from 'merchant/modules/invoices/details'
import CustomerCreation from 'merchant/containers/Customers/New'
import IssueConfirmModal from './IssueConfirmModal'
import * as ModalActions from 'merchant/modules/modals'
import InvoiceStatus from 'merchant/components/Invoices/InvoiceStatus'

const notificationClassMap = {
  sent: 'text-success',
  pending: 'text-warning'
}

function validate(values) {
  let errors = {}
  let lineItems = values.line_items.filter((item) => !!((item.item_id && item.item_id !== 'NULL') || item.id || item.name))

  if (!values.customer_id) {
    errors.customer_id = 'Please select a customer'
  }

  if (!lineItems.length) {
    errors.line_items = [{
      item_id: 'Please select an item'
    }]
  }
  return errors
}

const selector = formValueSelector('newInvoice')
@connect(
  (state) => {
    let customers = state.customers.customers
    return {
      customers,
      items: state.items.items,
      customer: findBy(customers, 'id', selector(state, 'customer_id')),
      invoice: selector(
        state,
        'id',
        'receipt',
        'status',
        'line_items',
        'short_url',
        'payment_id',
        'notes',
        'customer_details',
        'email_status',
        'sms_status',
        'paid_at',
        'payment_id'
      )
    }
  },
  {
    fetchCustomersForAutocomplete,
    fetchItemsForAutocomplete,
    saveInvoice,
    highLightInvoice,
    deleteInvoice,
    fetchInvoice,
    ...ModalActions
  }
)
@reduxForm({
  form: 'newInvoice',
  enableReInitialize: true,
  validate,
  initialValues: {
    date: Math.ceil(new Date().getTime()/1000),
    draft: 0,
    type: 'invoice',
    line_items: [
      {
        quantity: 1,
        amountInINR: '0.00'
      }
    ]
  }
})
export default class InvoicesNewContainer extends Component {
  static contextTypes = {
    ngRouter: PropTypes.object,
    confirm: PropTypes.func
  }

  constructor() {
    super(...arguments)
    this.state = {
      status: {}
    }
    this.save = ::this.save
    this.selectCustomerAndCloseModal = ::this.selectCustomerAndCloseModal
    this.quickCreateCustomer = ::this.quickCreateCustomer
    this.resendInvoice = ::this.resendInvoice
    this.deleteInvoice = ::this.deleteInvoice
    this.downloadInvoicePDF = ::this.downloadInvoicePDF
  }

  componentWillMount() {
    let promises = [
      this.props.fetchCustomersForAutocomplete(),
      this.props.fetchItemsForAutocomplete(),
    ]

    this.setState({
      isLoading: true
    })

    if (this.props.id) {
      promises.push(this.props.fetchInvoice(this.props.id))
    }

    Promise.all(promises).then(([customers, items, invoice]) => {
      if (invoice) {
        this.props.initialize(invoice)
      }

      this.setTitle(invoice)
      this.setState({
        isLoading: false
      })
    })
  }

  setTitle(invoice) {
    this.setState({
      title: invoice ? (invoice.receipt || invoice.id) : 'New Invoice'
    })
  }

  calculateItemsSubTotal() {
    let lineItems = this.props.invoice.line_items || []
    return lineItems.reduce((total, line_item) => {
      return total + (Number(line_item.quantity) * Number(line_item.amountInINR))
    }, 0).toFixed(2)
  }

  calculateInvoiceTotal() {
    return this.calculateItemsSubTotal()
  }

  selectCustomerAndCloseModal(customer) {
    this.props.change('customer_id', customer.id)
    this.props.closeModal()
  }

  quickCreateCustomer() {
    this.props.openModal({
      size: 'small',
      component: <CustomerCreation
        saveLabel='Create and add this customer'
        onSave={this.selectCustomerAndCloseModal}
        closeModal={this.props.closeModal}
      />
    })
  }

  save(props) {
    this.setState({
      isSaving: true
    })
    return this.props.saveInvoice(props).then((invoice) => {
      this.props.initialize(invoice)
      this.context.ngRouter.transitionTo('app.invoices.edit', invoice, {
        notify: false
      })
      this.setTitle(invoice)
      this.setState({
        isSaving: false
      })
      return invoice
    }).catch(({ errors }) => {
      this.setState({
        status: {
          type: 'error',
          message: errors
        },
        isSaving: false
      })
    })
  }

  saveAndIssue(props) {
    this.showIssueConfirmModal(props)
  }

  resendInvoice(props) {
    this.showIssueConfirmModal()
  }

  showIssueConfirmModal(props) {
    this.props.openModal({
      size: 'small',
      component: <IssueConfirmModal
        customer={this.props.customer}
        onIssue={(notifyProps) => {
          return this.save({
            ...props,
            ...notifyProps
          })
        }}
      />
    })
  }

  downloadInvoicePDF() {

  }

  navigateToList() {
    return this.context.ngRouter.transitionTo('app.invoices.list')
  }

  deleteInvoice() {
    if (!this.props.dirty) {
      return this.navigateToList()
    }

    let invoice = this.props.invoice
    this.context.confirm({
      header: 'Delete Invoice?',
      message: () => (
        <div class='text-semi-muted'>
          {
            invoice.id ?
              <p>The Invoice will be deleted. There is no coming back!. Are you sure?</p> :
              <p>The Invoice will be deleted and the customer will not be able to pay for it.</p>
          }
          <div>
            If any customers or items were added, they will still be available for use in other invoices.
          </div>
        </div>
      ),
      affirmativeLabel: 'Yes, Delete',
      affirmativePendingLabel: 'Deleting...',
      abortLabel: 'No, don\'t!',
      action: () => {
        return invoice.id ?
          this.props.deleteInvoice(invoice).then(() => {
            this.navigateToList()
          }).catch((err) => {
            this.setState({
              status: {
                type: 'error',
                message: err.errors
              }
            })
          }) :
          this.navigateToList()
      }
    })
  }

  render() {
    const {
      handleSubmit,
      customer,
      invoice = {},
    } = this.props

    let isNew = !invoice.id
    let status = invoice.status
    let isDraft = status === 'draft'
    let isIssued = status === 'issued'
    let isPaid = status === 'paid'
    let isExpired = status === 'expired'
    let locked = isPaid || isExpired

    let invoiceTotal = this.calculateInvoiceTotal()
    let customerDetails = ''
    if (customer) {
      let parts = customer.displayName.split('(')
      if (parts.length > 1) {
        customerDetails = parts[1].replace(')', '')
      }
    }

    return (
      <div class='react-root'>
        {
          this.state.isLoading ?
          <div class='page-spinner-container'>
            <Spinner />
          </div> :
          <div class='content-wrapper invoice-creation-container'>
            <form onSubmit={handleSubmit(this.save)}>
              <div class='row'>
                <div class='col-md-8'>
                  <div class='invoice-container pull-right'>
                    <ol class='breadcrumb breadcrumb__backNav'>
                      <li>
                        <a href='#/app/invoices/list' class='breadcrumb__backNav--link'>
                          <i class='fa fa-arrow-left'></i>
                          <span>All Invoices</span>
                        </a>
                      </li>
                      <li>
                        <h3 class='breadcrumb__backNav--heading'>
                          { this.state.title }
                        </h3>
                        {
                          isNew ?
                            <span class='label label-muted'>Unsaved</span> :
                            <InvoiceStatus status={status} />
                        }
                      </li>
                    </ol>

                    <Alert
                      type={this.state.status.type}
                      message={this.state.status.message}
                    />

                    <div class='invoice'>
                      <div class='row'>
                        <div class='col-md-6'>
                          <div class='inv__titlesection'>
                            <h3>Invoice</h3>
                            {
                              (locked && !invoice.receipt) ?
                                <InlineField
                                  formName='newInvoice'
                                  name='id'
                                  component='input'
                                  class='form-control input-xs'
                                  disabled={true}
                                  size={30}
                                /> :
                                <InlineField
                                  formName='newInvoice'
                                  name='receipt'
                                  component='input'
                                  class='form-control input-xs'
                                  placeholder='Receipt number'
                                  disabled={locked}
                                />
                            }
                          </div>

                          <InlineField
                            formName='newInvoice'
                            name='description'
                            component={AutoResizeTextarea}
                            class='form-control input-xs'
                            placeholder='Summary or brief this invoice'
                            rows='2'
                            disabled={isIssued || locked}
                          />
                        </div>
                        <div class='col-md-6 text-right'>
                          <label>AMOUNT DUE</label>
                          <h3 class='inv__amountdue'>₹ {invoiceTotal}</h3>
                        </div>
                      </div>

                      <div class='row'>
                        <div class='col-md-6'>
                          <label>BILLING TO</label>
                          <InlineField
                            formName='newInvoice'
                            name='customer_id'
                            component={TypeAhead}
                            options={this.props.customers}
                            selected={this.props.customer_id}
                            selectedLabel={(selectedCustomer) => {
                              return selectedCustomer.name || selectedCustomer.contact || selectedCustomer.email
                            }}
                            optionLabelPath='displayName'
                            placeholder='Select a customer'
                            onQuickAdd={this.quickCreateCustomer}
                            disabled={isIssued || locked}
                            onOptionChange={(selectedCustomer) => {
                              this.props.change('customer_id', selectedCustomer.id || '')
                            }}
                            normalizeValue={(value) => {
                              let selected = findBy(this.props.customers || [], 'id', value)
                              if (selected) {
                                return selected.name || selected.contact || selected.email
                              }
                              return value
                            }}
                          />

                          {
                            customerDetails ?
                              <div class='inv__customerdetails'>
                                <div>{customerDetails}</div>
                              </div> : ''
                          }
                        </div>
                        <div class='col-md-6 text-right'>
                          <label>INVOICE DATE</label>
                            <InlineField
                              formName='newInvoice'
                              name='date'
                              component={DatePickerField}
                              class='form-control'
                              rightAlign={true}
                              normalizeValue={(value) => {
                                if (value) {
                                  return moment.unix(value).format('DD MMM YYYY')
                                }
                                return value
                              }}
                              disabled={isIssued || locked}
                            />
                        </div>
                      </div>
                      <div class='row'>
                        <div class='col-md-12'>
                          <FieldArray
                            name='line_items'
                            component={LineItemTable}
                            items={this.props.items}
                            disabled={isIssued || locked}
                            invoiceTotal={invoiceTotal}
                          />
                        </div>
                      </div>

                      <div class='row'>
                        <div class='col-md-12'>
                          <InlineField
                            formName='newInvoice'
                            name='comment'
                            component={AutoResizeTextarea}
                            class='form-control input-xs'
                            rows={2}
                            placeholder='Customer Notes'
                            disabled={locked}
                          />
                        </div>
                      </div>

                      <div class='row'>
                        <div class='col-md-12'>
                          <InlineField
                            formName='newInvoice'
                            name='terms'
                            component={AutoResizeTextarea}
                            class='form-control input-xs'
                            rows={3}
                            placeholder='Terms and Conditions'
                            disabled={locked}
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class='col-md-4'>
                  <div class='inv__cta'>
                    <div class='btn-group-vertical'>
                      {
                        (isNew || isDraft) &&
                          <AsyncButton
                            type='button'
                            class='btn btn-primary btn-block btn-lg'
                            disabled={this.state.isSaving}
                            onClick={handleSubmit((props) => {
                              return this.saveAndIssue({
                                ...props,
                                ...{ draft: 0 }
                              })
                            })}
                          >
                            <i class='fa fa-check'></i>
                            <span>Finalize and Issue</span>
                          </AsyncButton>
                      }

                      {
                        isIssued &&
                          <AsyncButton
                            type='button'
                            class='btn btn-primary btn-block btn-lg'
                            disabled={this.state.isSaving}
                            onClick={handleSubmit(this.resendInvoice)}
                          >
                            <i class='fa fa-paper-plane'></i>
                            <span>Resend Invoice</span>
                          </AsyncButton>
                      }

                      {
                        !(isPaid || isExpired) &&
                          <AsyncButton
                            type='button'
                            class='btn btn-default btn-block btn-lg'
                            text='Save Changes'
                            pendingText='Saving...'
                            disabled={this.state.isSaving}
                            onClick={handleSubmit((props) => {
                              return this.save({
                                ...props,
                                ...{ draft: isIssued ? 0 : 1 }
                              })
                            })}
                          >
                            <i class='fa fa-floppy-o'></i>
                            <span>Save Changes</span>
                          </AsyncButton>
                      }

                      {
                        !(isNew || isDraft) &&
                          <button
                            type='button'
                            class='btn btn-default btn-block btn-lg'
                            onClick={this.downloadInvoicePDF}
                          >
                            <i class='fa fa-download'></i>
                            <span>Download PDF</span>
                          </button>
                      }

                      {
                        (isNew || isDraft) &&
                          <button
                            class='btn btn-default btn-block btn-lg'
                            onClick={this.deleteInvoice}
                            disabled={this.state.isSaving}
                          >
                            <i class='fa fa-times'></i>
                            <span>Delete Invoice</span>
                          </button>
                      }
                    </div>
                  </div>

                  {
                    !(isNew || isDraft) &&
                      <div class='inv__info'>
                        <h4>Invoice {invoice.status}</h4>
                        <dl>
                          {
                            isPaid ?
                            <div>
                              <dt>Payment Id</dt>
                              <dd>
                                <a href={`#/app/payments/${invoice.payment_id}`}>
                                  {invoice.payment_id}
                                </a>
                              </dd>

                              <dt>Paid On</dt>
                              <dd>
                                <Time
                                  value={invoice.paid_at}
                                  format='DD MMM YYYY, hh:mm:ss a'
                                />
                              </dd>
                            </div> :
                            <div>
                              <dt>Payment Link</dt>
                              <dd>
                                <Clipboard value={invoice.short_url} />
                              </dd>
                            </div>
                          }
                          {
                            invoice.email_status &&
                            <div>
                              <dt>Email Sent to</dt>
                              <dd>
                                {invoice.customer_details.customer_email}
                                <span
                                  style={{ marginLeft: '10px' }}
                                  class={`${notificationClassMap[invoice.email_status]}`}
                                >
                                  {invoice.email_status ? `(${invoice.email_status})` : ''}
                                </span>
                              </dd>
                            </div>
                          }

                          {
                            invoice.sms_status &&
                            <div>
                              <dt>SMS Sent to</dt>
                              <dd>
                                {invoice.customer_details.customer_contact}
                                <span
                                  style={{ marginLeft: '10px' }}
                                  class={`${notificationClassMap[invoice.sms_status]}`}
                                >
                                  {invoice.sms_status ? `(${invoice.sms_status})` : ''}
                                </span>
                              </dd>
                            </div>
                          }
                          {
                            isPaid &&
                            <div>
                              <dt>Payment Link</dt>
                              <dd>
                                <Clipboard value={invoice.short_url} />
                              </dd>
                            </div>
                          }
                        </dl>
                      </div>
                  }
                  {
                    Object.keys(invoice.notes || {}).length ?
                    <div class='inv__info'>
                      <h4>Internal Notes</h4>
                      <dl>
                        {
                          Object.keys(invoice.notes).map((key) => (
                            <div>
                              <dt>{key}</dt>
                              <dd>{invoice.notes[key]}</dd>
                            </div>
                          ))
                        }
                      </dl>
                    </div> : ''
                  }
                </div>
              </div>
            </form>
          </div>
        }
      </div>
    )
  }
}
