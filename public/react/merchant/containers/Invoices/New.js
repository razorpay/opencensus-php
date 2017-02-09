import { Component, PropTypes } from 'react'
import { Field, FieldArray, reduxForm, formValueSelector } from 'redux-form'
import { connect } from 'react-redux'
import AsyncButton from 'react-async-button'
import Alert from 'rzp/ui/Forms/Alert'
import InputField from 'rzp/ui/Forms/InputField'
import DatePickerField from 'rzp/ui/Forms/DatePickerField'
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
import Invoice from 'merchant/models/Invoice'
import * as ModalActions from 'merchant/modules/modals'

const selector = formValueSelector('newInvoice')
@connect(
  (state) => {
    let customers = state.customers.customers
    return {
      customers,
      items: state.items.items,
      customer: findBy(customers, 'id', selector(state, 'customer_id')),
      isIssued: selector(state, 'status') === 'issued',
      invoice_line_items: selector(state, 'line_items') || []
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
  initialValues: new Invoice({
    date: Math.ceil(new Date().getTime()/1000),
    draft: 0,
    type: 'invoice',
    line_items: [
      {
        quantity: 1,
        amountInINR: '0.00'
      }
    ]
  })
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
    return this.props.invoice_line_items.reduce((total, line_item) => {
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

  render() {
    const {
      handleSubmit,
      customer,
      isIssued
    } = this.props

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
                        <h3 class='breadcrumb__backNav--heading'>New Invoice</h3>
                        <span class='label label-draft'>Unsaved</span>
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
                            />
                          </div>

                          <InlineField
                            formName='newInvoice'
                            name='description'
                            component='textarea'
                            class='form-control input-xs'
                            placeholder='Summary or brief this invoice'
                            rows='2'
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
                              leftAlign={true}
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
                            component='textarea'
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
                        onClick={handleSubmit(this.save)}
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
                          onClick={handleSubmit(this.save)}
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
                </div>
              </div>
            </form>
          </div>
        }
      </div>
    )
  }
}
