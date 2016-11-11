import React, { Component } from 'react'
import { connect } from 'react-redux'
import Header from 'rzp/ui/Header'

import { fetchSubscriptions } from 'merchant/modules/subscriptions/list'
import { fetchPlans } from 'merchant/modules/plans'
import SubscriptionsList from 'merchant/components/Subscriptions/SubscriptionsList'

@connect(
  (state) => {
    let plansState = state.plans.toJS()
    let subscriptionsState = state.subscriptions.toJS()

    return {
      subscriptions: subscriptionsState.subscriptions,
      plans: plansState.plans,
      loading: subscriptionsState.loading && plansState.loading
    }
  },
  { fetchSubscriptions, fetchPlans }
)
export default class SubscriptionsListContainer extends Component {
  componentWillMount() {
    this.props.fetchSubscriptions()
    this.props.fetchPlans()
  }

  render() {
    let { loading, subscriptions, plans } = this.props

    return (
      <div>
        <Header title='Subscriptions'>
          <a href='#/app/subscriptions/new' className='pull-right btn btn-primary btn-rounded'>
            <i className='fa fa-plus'></i>
            <span>New Subscription</span>
          </a>
        </Header>

        <div className='content-wrapper'>
          <div className='panel panel-default'>
            <SubscriptionsList
              subscriptions={subscriptions}
              plans={plans}
              isLoading={loading}
            />
          </div>
        </div>
      </div>
    )
  }
}
