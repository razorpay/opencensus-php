import React, { Component } from 'react'
import { Field, reduxForm } from 'redux-form'
import { connect } from 'react-redux'
import AsyncButton from 'react-async-button'

import Header from 'rzp/ui/Header'
import DatePickerField from 'rzp/ui/Forms/DatePickerField'
import ReduxSelect2 from 'rzp/ui/Forms/ReduxSelect2'
import LineItemTable from 'merchant/components/Invoices/LineItemTable'
import { fetchCustomers } from 'merchant/modules/customers'


@connect(
  (state) => state.invoice.toJS(),
  { fetchCustomers }
)
@reduxForm({
  form: 'invoiceCreation',
  initialValues: {
    due_on: 30,
    notes: 'Thanks for your business'
  }
})
export default class InvoicesNewContainer extends Component {
  constructor() {
    super(...arguments)
    this.state = {
      customers: []
    }
    this.save = ::this.save
  }

  componentWillMount() {
    this.props.fetchCustomers().then((response) => {
      let customers = response.data.items.map((item) => {
        item.text = item.text || item.name
        return item
      })

      customers.unshift({})

      this.setState({
        customers
      })
    })
  }

  save() {

  }

  render() {
    const { handleSubmit } = this.props
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
                      name='customer_id'
                      id='customer_id'
                      data={this.state.customers}
                      options={{
                        placeholder: 'Select a Customer',
                        allowClear: true
                      }}
                      component={ReduxSelect2}
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

{/*
                <LineItemTable items={initialValues.items} />
*/}

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
              </form>
            </div>
          </div>
        </div>
      </div>
    )
  }
}
