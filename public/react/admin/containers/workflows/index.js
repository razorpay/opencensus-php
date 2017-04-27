import React, { Component } from 'react';
import { connect } from 'react-redux';
import Header from 'rzp/ui/Header';

// import SubscriptionsList from 'merchant/components/Subscriptions/SubscriptionsList'

export default class WorkflowsListContainer extends Component {
  render() {
    return (
      <div>
        <Header title="Workflows" />
      </div>
    );
  }
}
