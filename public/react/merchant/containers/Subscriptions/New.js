import React, { Component } from 'react'
import { Field, reduxForm } from 'redux-form'
import { connect } from 'react-redux'
import AsyncButton from 'react-async-button'
import { PowerSelect } from 'react-power-select'

import Modal from 'rzp/ui/modal'
import Header from 'rzp/ui/Header'
import DatePickerField from 'rzp/ui/Forms/DatePickerField'
import ReduxPowerSelect from 'rzp/ui/Forms/ReduxPowerSelect'

import { fetchCustomers } from 'merchant/modules/customers'
import { fetchPlans } from 'merchant/modules/plans'
import ModalContainer from 'merchant/containers/ModalContainer'
import CustomerCreation from 'merchant/containers/Customers/New'

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
export default class SubscriptionsNewContainer extends ModalContainer {
  constructor() {
    super(...arguments)
    this.save = ::this.save
    this.selectCustomerAndCloseModal = ::this.selectCustomerAndCloseModal
  }

  componentWillMount() {
    this.props.fetchCustomers()
    this.props.fetchPlans()
  }

  selectCustomerAndCloseModal(customer) {
    this.props.change('customer', customer)
  }

  quickCreateCustomer() {
    this.openModal()
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
          <a href='#/app/subscriptions' class='pull-right btn btn-link btn-sm'>
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

        <div class='content-wrapper'>
          <div class='panel panel-default'>
            <div class='panel-body'>
              <form class='form-horizontal' onSubmit={handleSubmit(this.save)}>
                <div class='form-group'>
                  <label for='customer' class='col-md-2 control-label'>
                    Customer Name
                  </label>
                  <div class='col-md-4'>
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

                <div class='form-group'>
                  <label for='plan' class='col-md-2 control-label'>
                    Plan Name
                  </label>
                  <div class='col-md-4'>
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

                <div class='form-group'>
                  <label for='due_on' class='col-md-2 control-label'>
                    Quantity
                  </label>
                  <div class='col-md-4'>
                    <Field
                      name='quantity'
                      id='quantity'
                      type='number'
                      component='input'
                      min={1}
                      class='form-control'
                    />
                  </div>
                </div>

                <div class='form-group'>
                  <label for='start_at' class='col-md-2 control-label'>
                    Starts on
                  </label>
                  <div class='col-md-4'>
                    <Field
                      name='start_at'
                      id='start_at'
                      component={DatePickerField}
                      class='form-control'
                    />
                  </div>
                </div>

                <div class='form-group'>
                  <label for='end_at' class='col-md-2 control-label'>
                    Ends on
                  </label>
                  <div class='col-md-4'>
                    <Field
                      name='end_at'
                      id='end_at'
                      component={DatePickerField}
                      class='form-control'
                    />
                  </div>
                </div>

                <div class='form-group'>
                  <label for='notes' class='col-md-2 control-label'>
                    Notes
                  </label>
                  <div class='col-md-4'>
                    <Field
                      name='notes'
                      id='notes'
                      component='textarea'
                      class='form-control'
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
                      href='#/app/subscriptions'
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
