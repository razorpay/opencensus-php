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
import { required } from 'rzp/utils/validators'

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
    return {
      customers: state.customers.customers,
      items: state.items.items,
      customer: selector(state, 'customer'),
      isIssued: selector(state, 'status') === 'issued'
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
    draft: 1,
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
    this.props.fetchCustomersForAutocomplete()
    this.props.fetchItemsForAutocomplete()
    if (this.props.id) {
      this.setState({
        isLoading: true
      })
      this.props.fetchInvoice(this.props.id).then((invoice) => {
        this.props.initialize(invoice)
        this.setState({
          isLoading: false
        })
      })
    }
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
    return this.props.saveInvoice(props).then((invoice) => {
      this.context.ngRouter.transitionTo('app.invoices.list').then(() => {
        this.props.highLightInvoice(invoice.id)
      })
    }).catch(({ errors }) => {
      this.setState({
        errors
      })
    })
  }

  render() {
    const { handleSubmit } = this.props
    let isIssued = this.props.isIssued

    return (
      <div class='react-root'>
        {
          this.state.isLoading ?
          <div class='page-spinner-container'>
            <Spinner />
          </div> :
          <div class='content-wrapper invoice-creation-container'>
            <Alert
              type='error'
              message={this.state.errors}
            />

            <div class='panel panel-default'>
              <div class='panel-body'>
                <form onSubmit={handleSubmit(this.save)}>
                  <div class='row'>
                    <div class='col-md-5'>
                      <div class='form-group'>
                        <label>Invoice Summary</label>
                        <Field
                          name='description'
                          component='textarea'
                          class='form-control'
                          autoFocus={true}
                          disabled={isIssued}
                        />
                      </div>
                    </div>

                    <div class='col-md-3 pull-right'>
                      <div class='form-group'>
                        <label>Receipt No</label>
                        <Field
                          name='receipt'
                          component={InputField}
                          class='form-control'
                        />
                      </div>
                    </div>
                  </div>

                  <div class='row'>
                    <div class='col-md-5'>
                      <div class='form-group'>
                        <label class='label-required'>Customer Name</label>
                        <Field
                          name='customer_id'
                          component={TypeAhead}
                          options={this.props.customers}
                          selected={this.props.customer_id}
                          optionLabelPath='displayName'
                          placeholder='Select a customer'
                          onQuickAdd={this.quickCreateCustomer}
                          disabled={isIssued}
                          onChange={(selectedCustomer) => {
                            this.props.change('customer_id', selectedCustomer.id || '')
                          }}
                          validate={required('Please provide the customer')}
                        />
                      </div>
                    </div>

                    <div class='col-md-5 pull-right'>
                      <div class='row'>
                        <div class='col-md-7 pull-right'>
                          <div class='form-group'>
                            <label>Invoice Date</label>
                            <Field
                              name='date'
                              component={DatePickerField}
                              class='form-control'
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>


                  <FieldArray
                    name='line_items'
                    component={LineItemTable}
                    items={this.props.items}
                    disabled={isIssued}
                  />

                  <div class='form-group'>
                    <label>Terms & Conditions</label>
                    <Field
                      name='terms'
                      component='textarea'
                      class='form-control'
                      rows={3}
                    />
                  </div>

                  <hr />

                  <div class='btn-toolbar'>
                    <AsyncButton
                      type='button'
                      class='btn btn-primary btn-rounded'
                      text='Save & Send'
                      pendingText='Saving...'
                      onClick={handleSubmit(this.save)}
                    />
                    <AsyncButton
                      type='button'
                      class='btn btn-default btn-rounded'
                      text='Save as draft'
                      pendingText='Saving...'
                      onClick={handleSubmit(this.save)}
                    />
                    <a
                      href='#/app/invoices/list'
                      class='btn btn-default btn-rounded'
                    >
                      Cancel
                    </a>
                  </div>
                </form>
              </div>
            </div>
          </div>
        }


      </div>
    )
  }
}
