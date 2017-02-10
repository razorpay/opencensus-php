import { Component, PropTypes } from 'react'
import { Field, FieldArray, reduxForm, formValueSelector } from 'redux-form'
import { connect } from 'react-redux'
import AsyncButton from 'react-async-button'
import Alert from 'rzp/ui/Forms/Alert'
import InputField from 'rzp/ui/Forms/InputField'
import DatePickerField from 'rzp/ui/Forms/DatePickerField'
import AutoResizeTextarea from 'rzp/ui/Forms/AutoResizeTextarea'
import PowerSelect from 'rzp/ui/Select/PowerSelect'
import TypeAhead from 'rzp/ui/Select/TypeAhead'
import Spinner from 'rzp/ui/Spinner'
import InlineField from 'rzp/ui/Forms/InlineField'
import { required } from 'rzp/utils/validators'
import { findBy } from 'rzp/utils/rzp-utils'

import LineItemTable from './LineItemTable'
import { fetchCustomersForAutocomplete } from 'merchant/modules/customers'
import { fetchItemsForAutocomplete } from 'merchant/modules/items'
import { saveInvoice, highLightInvoice } from 'merchant/modules/invoices/list'
import { fetchInvoice } from 'merchant/modules/invoices/details'
import CustomerCreation from 'merchant/containers/Customers/New'
import IssueConfirmModal from './IssueConfirmModal'
import * as ModalActions from 'merchant/modules/modals'
import InvoiceStatus from 'merchant/components/Invoices/InvoiceStatus'

const notificationClassMap = {
  sent: 'text-success',
  pending: 'text-warning'
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
        'status',
        'line_items',
        'short_url',
        'payment_id',
        'notes',
        'customer_details',
        'email_status',
        'sms_status'
      )
    }
  },
  {
    fetchCustomersForAutocomplete,
    fetchItemsForAutocomplete,
    saveInvoice,
    highLightInvoice,
    fetchInvoice,
    ...ModalActions
  }
)
@reduxForm({
  form: 'newInvoice',
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
    ngRouter: PropTypes.object
  }

  constructor() {
    super(...arguments)
    this.state = {
      errors: null
    }
    this.save = ::this.save
    this.selectCustomerAndCloseModal = ::this.selectCustomerAndCloseModal
    this.quickCreateCustomer = ::this.quickCreateCustomer
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

      this.setState({
        isLoading: false
      })
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
      component: <CustomerCreation
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
      this.context.ngRouter.transitionTo('app.invoices.list').then(() => {
        this.props.highLightInvoice(invoice.id)
      })
      this.setState({
        isSaving: false
      })
    }).catch(({ errors }) => {
      this.setState({
        errors,
        isSaving: false
      })
    })
  }

  saveAndIssue(props) {
    this.props.openModal({
      size: 'small',
      component: <IssueConfirmModal
        customer={this.props.customer}
        onIssue={this.save}
      />
    })
  }

  render() {
    const {
      handleSubmit,
      customer,
      invoice = {},
    } = this.props

    let isIssued = invoice.status === 'issued'
    let isNew = !!this.props.id
    let invoiceTotal = this.calculateItemsSubTotal()
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
                          { this.props.id || 'New Invoice' }
                        </h3>
                        {
                          isNew ?
                            <InvoiceStatus status={invoice.status} /> :
                            <span class='label label-muted'>Unsaved</span>
                        }
                      </li>
                    </ol>

                    <Alert
                      type='error'
                      message={this.state.errors}
                    />

                    <div class='invoice'>
                      <div class='row'>
                        <div class='col-md-6'>
                          <div class='inv__titlesection'>
                            <h3>Invoice</h3>
                            <InlineField
                              formName='newInvoice'
                              name='receipt'
                              component='input'
                              class='form-control input-xs'
                              placeholder='Receipt number'
                              size='25'
                              disabled={isIssued}
                            />
                          </div>

                          <InlineField
                            formName='newInvoice'
                            name='description'
                            component={AutoResizeTextarea}
                            class='form-control input-xs'
                            placeholder='Summary or brief this invoice'
                            rows='2'
                            disabled={isIssued}
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
                            disabled={isIssued}
                            onOptionChange={(selectedCustomer) => {
                              this.props.change('customer_id', selectedCustomer.id || '')
                            }}
                            validate={required()}
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
                            />
                        </div>
                      </div>
                      <div class='row'>
                        <div class='col-md-12'>
                          <FieldArray
                            name='line_items'
                            component={LineItemTable}
                            items={this.props.items}
                            disabled={isIssued}
                            invoiceTotal={invoiceTotal}
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
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class='col-md-4'>
                  <div class='inv__cta'>
                    <div>
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

                      <div class='btn-group-vertical'>
                        <AsyncButton
                          type='button'
                          class='btn btn-default btn-block btn-lg'
                          text='Save Changes'
                          pendingText='Saving...'
                          disabled={this.state.isSaving}
                          onClick={handleSubmit((props) => {
                            return this.save({
                              ...props,
                              ...{ draft: 1 }
                            })
                          })}
                        >
                          <i class='fa fa-floppy-o'></i>
                          <span>Save Changes</span>
                        </AsyncButton>
                        <a
                          href='#/app/invoices/list'
                          class='btn btn-default btn-block btn-lg'
                          disabled={this.state.isSaving}
                        >
                          <i class='fa fa-times'></i>
                          <span>Delete Invoice</span>
                        </a>
                      </div>
                    </div>
                  </div>

                  {
                    invoice.status && invoice.status !== 'draft' &&
                    <div class='inv__info'>
                      <h4>Invoice {invoice.status}</h4>
                      <dl>
                        <dt>Payment Link</dt>
                        <dd>{invoice.short_url}</dd>

                        {
                          invoice.customer_details.customer_email &&
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
                          invoice.customer_details.customer_contact &&
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
