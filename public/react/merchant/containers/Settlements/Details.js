import React, { Component } from 'react'
import { connect } from 'react-redux'
import Header from 'rzp/ui/Header'
import SettlementDetails from 'merchant/components/Settlements/Details'
import * as SettlementActions from 'merchant/modules/settlements/details'

@connect(
  (state) => state.settlement,
  SettlementActions
)
export default class SettlementDetailsContainer extends Component {
  constructor() {
    super(...arguments)
    this.fetchBreakupDetails = ::this.fetchBreakupDetails
  }

  componentWillMount() {
    this.props.fetchSettlement(this.props.id)
  }

  fetchBreakupDetails(settlement) {
    return this.props.fetchBreakupDetails(settlement)
  }

  render() {
    let {
      loading,
      error,
      settlement,
      breakupDetails,
    } = this.props
    let statusMsg = {}

    if (error) {
      statusMsg = {
        type: 'error',
        message: this.props.error
      }
    }

    return (
      <div class='react-root'>
        <Header title='Settlement Detail' />

        <div class='content-wrapper'>
          <SettlementDetails
            settlement={settlement}
            isLoading={loading}
            statusMsg={statusMsg}
            onToggleBreakupDetails={this.fetchBreakupDetails}
            breakupDetails={breakupDetails}
          />
        </div>
      </div>
    )
  }
}
