import React, { Component } from 'react'
import { Field, reduxForm } from 'redux-form'
import { connect } from 'react-redux'
import AsyncButton from 'react-async-button'

import Header from 'rzp/ui/Header'
import DatePickerField from 'rzp/ui/Forms/DatePickerField'
import ReduxSelect2 from 'rzp/ui/Forms/ReduxSelect2'
import { fetchCustomers } from 'merchant/modules/customers/list'

@connect(
  null,
  { fetchCustomers }
)
@reduxForm({
  form: 'newSubscription',
  initialValues: {
  }
})
export default class SubscriptionsNewContainer extends Component {
  constructor() {
    super(...arguments)
    this.state = {
      customers: []
    }
    this.save = ::this.save
  }

  componentWillMount() {
    this.props.fetchCustomers().then((customers) => {
      customers = customers.map((item) => {
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
        <Header title='New Subscription'>
          <a href='#/app/subscriptions' className='pull-right btn btn-link btn-sm'>
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
                    Plan Name
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
                  <label htmlFor='due_on' className='col-md-2 control-label'>
                    Quantity
                  </label>
                  <div className='col-md-4'>
                    <Field
                      name='quantity'
                      id='quantity'
                      type='number'
                      component='input'
                      className='form-control'
                    />
                  </div>
                </div>

                <div className='form-group'>
                  <label htmlFor='start_at' className='col-md-2 control-label'>
                    Starts on
                  </label>
                  <div className='col-md-4'>
                    <Field
                      name='start_at'
                      id='start_at'
                      component={DatePickerField}
                      className='form-control'
                    />
                  </div>
                </div>

                <div className='form-group'>
                  <label htmlFor='end_at' className='col-md-2 control-label'>
                    Ends on
                  </label>
                  <div className='col-md-4'>
                    <Field
                      name='end_at'
                      id='end_at'
                      component={DatePickerField}
                      className='form-control'
                    />
                  </div>
                </div>

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
              </form>
            </div>
          </div>
        </div>
      </div>
    )
  }
}
