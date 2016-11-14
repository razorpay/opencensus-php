import React, { Component } from 'react'
import { Field, FieldArray, reduxForm, formValueSelector } from 'redux-form'
import { connect } from 'react-redux'
import AsyncButton from 'react-async-button'

import Header from 'rzp/ui/Header'
import DatePickerField from 'rzp/ui/Forms/DatePickerField'
import ReduxPowerSelect from 'rzp/ui/Forms/ReduxPowerSelect'
import LineItemTable from 'merchant/components/Invoices/LineItemTable'
import { fetchCustomers } from 'merchant/modules/customers'
import { fetchPlans } from 'merchant/modules/plans'

const selector = formValueSelector('newInvoice')
@connect(
  (state) => {
    let plansState = state.plans.toJS()
    let customersState = state.customers.toJS()

    return {
      customers: customersState.customers,
      plans: plansState.plans,
      selectedCustomer: selector(state, 'customer')
    }
  },
  { fetchCustomers, fetchPlans }
)
@reduxForm({
  form: 'newInvoice',
  initialValues: {
    due_on: 30,
    notes: 'Thanks for your business',
    items: [
      {
        quantity: 1,
        rate: 0.00
      }
    ]
  }
})
export default class InvoicesNewContainer extends Component {
  constructor() {
    super(...arguments)
    this.save = ::this.save
  }

  componentWillMount() {
    this.props.fetchCustomers()
    this.props.fetchPlans()
  }

  save() {
    debugger
  }

  render() {
    const { handleSubmit } = this.props
    let selectedCustomer = this.props.selectedCustomer

    return (
      <div>
        <Header title='New Invoice'>
          <a href='#/app/invoices' className='pull-right btn btn-link btn-sm'>
            <i className='fa fa-close'></i>
          </a>
        </Header>


        <div className='content-wrapper'>
          <div className='panel panel-default'>
            <div className='panel-body'>
              <form className='form-horizontal' onSubmit={handleSubmit(this.save)}>
                <div className='form-group'>
                  <label htmlFor='customer_name' className='col-md-2 control-label'>
                    Customer Name
                  </label>
                  <div className='col-md-4'>
                    <Field
                      name='customer'
                      component={ReduxPowerSelect}
                      options={this.props.customers}
                      selected={selectedCustomer}
                      selectedLabel='name'
                      optionComponent={(option) => <span>{option.name}</span>}
                      searchIndices={['name']}
                      placeholder='Select a customer'
                      afterOptionsComponent={({ select }) => (
                        <div
                          class='quick-create'
                          onClick={() => {
                            this.quickCreateCustomer()
                            select.close()
                          }}
                        >
                          <i class='fa fa-plus'></i>
                          <span>Add New Customer</span>
                        </div>
                      )}
                    />
                  </div>
                </div>


                <div className='form-group'>
                  <label htmlFor='invoice_date' className='col-md-2 control-label'>
                    Invoice Date
                  </label>
                  <div className='col-md-4'>
                    <Field
                      name='invoice_date'
                      id='invoice_date'
                      component={DatePickerField}
                      className='form-control'
                    />
                  </div>
                </div>

                <div className='form-group'>
                  <label htmlFor='due_on' className='col-md-2 control-label'>
                    Due Date
                  </label>
                  <div className='col-md-4'>
                    <Field
                      name='due_on'
                      id='due_on'
                      component='input'
                      className='form-control'
                    />
                  </div>
                </div>

                <FieldArray
                  name='items'
                  component={LineItemTable}
                  plans={this.props.plans}
                />

                <div className='form-group'>
                  <label htmlFor='notes' className='col-md-2 control-label'>
                    Notes
                  </label>
                  <div className='col-md-4'>
                    <Field
                      name='notes'
                      id='notes'
                      component='textarea'
                      className='form-control'
                    />
                  </div>
                </div>

                <div className='form-group'>
                  <label htmlFor='terms_and_conditions' className='col-md-2 control-label'>
                    Terms and Conditions
                  </label>
                  <div className='col-md-4'>
                    <Field
                      name='terms_and_conditions'
                      id='terms_and_conditions'
                      component='textarea'
                      className='form-control'
                    />
                  </div>
                </div>

                <div class='col-md-offset-2'>
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
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    )
  }
}
