import { Component, PropTypes } from 'react'
import { Field, FieldArray, reduxForm, formValueSelector } from 'redux-form'
import { connect } from 'react-redux'
import AsyncButton from 'react-async-button'
import Alert from 'rzp/ui/Forms/Alert'
import Modal from 'rzp/ui/Modal'
import Header from 'rzp/ui/Header'
import InputField from 'rzp/ui/Forms/InputField'
import DatePickerField from 'rzp/ui/Forms/DatePickerField'
import PowerSelect from 'rzp/ui/Select/PowerSelect'

import LineItemTable from './LineItemTable'
import { fetchCustomers } from 'merchant/modules/customers'
import { fetchItems } from 'merchant/modules/items'
import { createInvoice } from 'merchant/modules/invoices/list'
import CustomerCreation from 'merchant/containers/Customers/New'
import ModalContainer from 'merchant/containers/ModalContainer'

const selector = formValueSelector('newInvoice')
@connect(
  (state) => {
    let itemsState = state.items.toJS()
    let customersState = state.customers.toJS()

    return {
      customers: customersState.customers,
      items: itemsState.items,
      customer: selector(state, 'customer')
    }
  },
  { fetchCustomers, fetchItems, createInvoice }
)
@reduxForm({
  form: 'newInvoice',
  initialValues: {
    date: Math.ceil(new Date().getTime()/1000),
    // due_on: 30,
    // notes: 'Thanks for your business',
    line_items: [
      {
        quantity: 1,
        amount_in_inr: '0.00'
      }
    ]
  }
})
export default class InvoicesNewContainer extends ModalContainer {
  static contextTypes = {
    ngRouter: PropTypes.object
  }

  constructor() {
    super(...arguments)
    this.state.errors = null
    this.save = ::this.save
    this.selectCustomerAndCloseModal = ::this.selectCustomerAndCloseModal
    this.quickCreateCustomer = ::this.quickCreateCustomer
  }

  componentWillMount() {
    this.props.fetchCustomers()
    this.props.fetchItems()
  }

  selectCustomerAndCloseModal(customer) {
    this.props.change('customer_id', customer.id)
    this.closeModal()
  }

  quickCreateCustomer() {
    this.openModal()
  }

  save(fieldProps) {
    let { line_items, ...invoiceParams } = fieldProps
    invoiceParams.line_items = line_items.map((lineItem) => ({
      item_id: lineItem.id,
      quantity: lineItem.quantity
    }))

    invoiceParams.type = 'invoice'
    return this.props.createInvoice(invoiceParams).then((reponse) => {
      this.context.ngRouter.transitionTo('app.invoices')
    }).catch(({ errors }) => {
      this.setState({
        errors
      })
    })
  }

  render() {
    const { handleSubmit } = this.props

    return (
      <div>
        <Header title='New Invoice'>
          <a href='#/app/invoices' class='pull-right btn btn-link btn-sm'>
            <i class='fa fa-close'></i>
          </a>
        </Header>

        <Modal
          isOpen={this.state.isModalOpen}
          onRequestClose={this.closeModal}
          closeTimeoutMS={300}
        >
          <CustomerCreation
            onSave={this.selectCustomerAndCloseModal}
            closeModal={this.closeModal}
          />
        </Modal>

        <div class='content-wrapper invoice-creation-container'>
          <Alert
            type='error'
            message={this.state.errors}
          />

          <div class='panel panel-default'>
            <div class='panel-body'>
              <form onSubmit={handleSubmit(this.save)}>
                <div class='row'>
                  <div class='col-md-4'>
                    <div class='form-group'>
                      <label>Invoice No</label>
                      <Field
                        name='invoice_number'
                        component={InputField}
                        class='form-control'
                        placeholder='Enter Invoice NO'
                      />
                    </div>
                  </div>
                </div>

                <div class='row'>
                  <div class='col-md-4'>
                    <div class='form-group'>
                      <label>Customer Name</label>
                      <Field
                        name='customer_id'
                        component={PowerSelect}
                        options={this.props.customers}
                        selected={this.props.customer_id}
                        optionLabelPath='name'
                        placeholder='Select a customer'
                        onQuickAdd={this.quickCreateCustomer}
                      />
                    </div>
                  </div>

                  <div class='col-md-5 pull-right'>
                    <div class='row'>
                      <div class='col-md-7'>
                        <div class='form-group'>
                          <label>Invoice Date</label>
                          <Field
                            name='date'
                            component={DatePickerField}
                            class='form-control'
                          />
                        </div>
                      </div>
{/*
                      <div class='col-md-5'>
                        <div class='form-group'>
                          <label>Due On (days)</label>
                          <Field
                            name='due_on'
                            component='input'
                            type='number'
                            min={1}
                            class='form-control text-right'
                          />
                        </div>
                      </div>
*/}
                    </div>
                  </div>
                </div>


                <FieldArray
                  name='line_items'
                  component={LineItemTable}
                  items={this.props.items}
                />
{/*
                <div class='form-group'>
                  <label>Invoice Notes</label>
                  <Field
                    name='notes'
                    component='textarea'
                    class='form-control'
                  />
                </div>
*/}
                <div class='btn-toolbar'>
                  <AsyncButton
                    type='button'
                    class='btn btn-primary btn-rounded'
                    text='Save'
                    pendingText='Saving...'
                    onClick={handleSubmit(this.save)}
                  />
                  <a
                    href='#/app/invoices'
                    class='btn btn-default btn-rounded'
                  >
                    Cancel
                  </a>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    )
  }
}
