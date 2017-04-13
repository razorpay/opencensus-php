import React, { Component } from 'react'
import { connect } from 'react-redux'
import Pager from 'rzp/ui/Pager'
import Alert from 'rzp/ui/Forms/Alert'
import Header from 'rzp/ui/Header'
import PaymentsList from 'merchant/components/Payments/PaymentsList'
import ListContainer from 'merchant/containers/ListContainer'
import PaymentsListFilter from 'merchant/components/Payments/PaymentsListFilter'
import { fetchPayments } from 'merchant/modules/payments/list'

@connect(
  (state) => state.payments,
  { fetchPayments }
)
export default class PaymentsListContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchPayments(params)
  }

  render() {
    let { loading, payments=[], error } = this.props

    return (
      <div class='react-root'>
        <Header title='Payments' />

        <div class='content-wrapper'>
          <div class='panel panel-default'>
            <div class='panel-heading'>
              Payments List
            </div>

            <div class='panel-body'>
              <PaymentsListFilter
                form='paymentListFilter'
                count={this.state.count}
                onSubmit={this.search}
              />
            </div>

            {
              error &&
                <Alert
                  type='error'
                  message={error}
                />
            }

            <PaymentsList
              payments={payments}
              isLoading={loading}
            />

            <Pager
              count={this.state.count}
              skip={this.state.skip}
              length={payments.length}
              onClick={this.fetchAll}
            />
          </div>
        </div>
      </div>
    )
  }
}
