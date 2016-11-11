import React, { Component } from 'react'
import { Field, reduxForm } from 'redux-form'
import { connect } from 'react-redux'
import AsyncButton from 'react-async-button'
import { PowerSelect } from 'react-power-select'

import Header from 'rzp/ui/Header'
import DatePickerField from 'rzp/ui/Forms/DatePickerField'
import ReduxSelect2 from 'rzp/ui/Forms/ReduxSelect2'
import ReduxPowerSelect from 'rzp/ui/Forms/ReduxPowerSelect'
import { fetchCustomers } from 'merchant/modules/customers/list'
import { fetchPlans } from 'merchant/modules/plans'

@connect(
  (state) => {
    let plansState = state.plans.toJS()
    let customersState = state.customers.toJS()

    return {
      customers: customersState.customers,
      plans: plansState.plans
    }
  },
  { fetchCustomers, fetchPlans }
)
@reduxForm({
  form: 'newSubscription',
  initialValues: {
    quantity: 1
  }
})
export default class SubscriptionsNewContainer extends Component {
  constructor() {
    super(...arguments)
    this.save = ::this.save
  }

  componentWillMount() {
    this.props.fetchCustomers()
    this.props.fetchPlans()
  }

  save() {
  }

  render() {
    const { handleSubmit } = this.props
    let selectedCustomer = this.props.customer
    let selectedPlan = this.props.plan

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
                  <label htmlFor='customer' className='col-md-2 control-label'>
                    Customer Name
                  </label>
                  <div className='col-md-4'>
                    <Field
                      name='customer'
                      id='customer'
                      component={ReduxPowerSelect}
                      options={this.props.customers}
                      selected={selectedCustomer}
                      selectedLabel={(option) => <b>{option.name}</b>}
                      optionComponent={(option) => <span>{option.name}</span>}
                      searchIndices={['name']}
                      placeholder='Select a customer'
                    />
                  </div>
                </div>

                <div className='form-group'>
                  <label htmlFor='plan' className='col-md-2 control-label'>
                    Plan Name
                  </label>
                  <div className='col-md-4'>
                    <Field
                      name='plan'
                      id='plan'
                      component={ReduxPowerSelect}
                      options={this.props.plans}
                      selected={selectedPlan}
                      selectedLabel={(option) => <b>{option.name}</b>}
                      optionComponent={(option) => <span>{option.name}</span>}
                      searchIndices={['name']}
                      placeholder='Select a plan'
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
                      min={1}
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

                <div className='col-md-offset-2'>
                  <div className='btn-toolbar'>
                    <AsyncButton
                      type='button'
                      className='btn btn-primary'
                      text='Save'
                      pendingText='Saving...'
                      onClick={handleSubmit(this.save)}
                    />
                    <a
                      href='#/app/subscriptions'
                      className='btn btn-default'
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
