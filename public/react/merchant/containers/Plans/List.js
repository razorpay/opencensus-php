import React, { Component } from 'react'
import { connect } from 'react-redux'
import { reduxForm } from 'redux-form'
import Modal from 'rzp/ui/Modal'
import Header from 'rzp/ui/Header'
import { fetchPlans } from 'merchant/modules/plans'
import PlansList from 'merchant/components/Plans/PlansList'
import PlanCreation from 'merchant/containers/Plans/New'
import ModalContainer from 'merchant/containers/ModalContainer'

@connect(
  (state) => state.plans.toJS(),
  { fetchPlans }
)
@reduxForm({
  form: 'newPlan',
})
export default class PlansListContainer extends ModalContainer {
  constructor() {
    super(...arguments)
    this.editPlan = ::this.editPlan
    this.deletePlan = ::this.deletePlan
  }

  componentWillMount() {
    this.props.fetchPlans()
  }

  editPlan(plan) {
    this.props.initialize(plan)
    this.openModal()
  }

  deletePlan() {

  }

  render() {
    let { loading, plans } = this.props

    return (
      <div>
        <Header title='Plans'>
          <button
            class='pull-right btn btn-primary btn-rounded'
            onClick={this.openModal}
          >
            <i class='fa fa-plus'></i>
            <span>New Plan</span>
          </button>
        </Header>

        <div class='content-wrapper'>
          <div class='panel panel-default'>
            <PlansList
              plans={plans}
              isLoading={loading}
              onEdit={this.editPlan}
              onDelete={this.deletePlan}
            />
          </div>
        </div>

        <Modal
          isOpen={this.state.isModalOpen}
          onRequestClose={this.closeModal}
          closeTimeoutMS={300}
        >
          <PlanCreation
            onSave={this.closeModal}
            closeModal={this.closeModal}
          />
        </Modal>
      </div>
    )
  }
}
