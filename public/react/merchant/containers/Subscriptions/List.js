import React, { Component } from 'react'
import { connect } from 'react-redux'
import Header from 'rzp/ui/Header'

import { fetchSubscriptions } from 'merchant/modules/subscriptions/list'
import SubscriptionsList from 'merchant/components/Subscriptions/SubscriptionsList'

@connect(
  (state) => state.subscriptions.toJS(),
  { fetchSubscriptions }
)
export default class SubscriptionsListContainer extends Component {
  componentWillMount() {
    this.props.fetchSubscriptions()
  }

  render() {
    let { loading, subscriptions } = this.props

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
            <SubscriptionsList subscriptions={subscriptions} isLoading={loading} />
          </div>
        </div>
      </div>
    )
  }
}
