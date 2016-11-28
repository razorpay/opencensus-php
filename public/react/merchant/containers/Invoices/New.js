import React, { Component } from 'react'
import { Field, FieldArray, reduxForm, formValueSelector } from 'redux-form'
import { connect } from 'react-redux'
import AsyncButton from 'react-async-button'

import Modal from 'rzp/ui/Modal'
import Header from 'rzp/ui/Header'
import InputField from 'rzp/ui/Forms/InputField'
import DatePickerField from 'rzp/ui/Forms/DatePickerField'
import PowerSelect from 'rzp/ui/Select/PowerSelect'

import LineItemTable from './LineItemTable'
import { fetchCustomers } from 'merchant/modules/customers'
import { fetchItems } from 'merchant/modules/items'
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
  { fetchCustomers, fetchItems }
)
@reduxForm({
  form: 'newInvoice',
  initialValues: {
    invoice_date: Math.ceil(new Date().getTime()/1000),
    due_on: 30,
    notes: 'Thanks for your business',
    line_items: [
      {
        quantity: 1,
        rate: '0.00'
      }
    ]
  }
})
export default class InvoicesNewContainer extends ModalContainer {
  constructor() {
    super(...arguments)
    this.save = ::this.save
    this.selectCustomerAndCloseModal = ::this.selectCustomerAndCloseModal
    this.quickCreateCustomer = ::this.quickCreateCustomer
  }

  componentWillMount() {
    this.props.fetchCustomers()
    this.props.fetchItems()
  }

  selectCustomerAndCloseModal(customer) {
    this.props.change('customer', customer)
    this.props.change('customer_id', customer.id)
    this.closeModal()
  }

  quickCreateCustomer() {
    this.openModal()
  }

  save(props) {
    alert(JSON.stringify(props))
  }

  render() {
    const { handleSubmit } = this.props
    let selectedCustomer = this.props.customer || {}

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
                      {
                        selectedCustomer.address &&
                        <small class='text-muted'>
                          <b>Billing Address: </b>
                          {selectedCustomer.address}
                        </small>
                      }
                    </div>
                  </div>

                  <div class='col-md-6 pull-right'>
                    <div class='row'>
                      <div class='col-md-6'>
                        <div class='form-group'>
                          <label>Invoice Date</label>
                          <Field
                            name='invoice_date'
                            component={DatePickerField}
                            class='form-control'
                          />
                        </div>
                      </div>

                      <div class='col-md-6'>
                        <div class='form-group'>
                          <label>Due Date</label>
                          <Field
                            name='due_date'
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
                />

                <div class='form-group'>
                  <label>Invoice Notes</label>
                  <Field
                    name='notes'
                    component='textarea'
                    class='form-control'
                  />
                </div>

                <div class='btn-toolbar'>
                  <AsyncButton
                    type='button'
                    class='btn btn-primary'
                    text='Save'
                    pendingText='Saving...'
                    onClick={handleSubmit(this.save)}
                  />
                  <a
                    href='#/app/invoices'
                    class='btn btn-default'
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
