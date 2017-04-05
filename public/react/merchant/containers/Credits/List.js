import React, { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { fetchCreditBalance } from 'merchant/modules/credits'
import Header from 'rzp/ui/Header'
import CreditsDetails from 'merchant/components/Credits'

@connect(
  (state) => {
    return {
      credits: state.credits,
      user: state.session.user,
    }
  },
  { fetchCreditBalance }
)

export default class CreditsListContainer extends Component {
  componentDidMount() {
    if (this.props.user.current) {
      this.props.fetchCreditBalance();
    }
  }

  render() {
    return (
      <div class='react-root'>
        <Header title='Your Credits' showMode={false} />

        <div class='content-wrapper'>
          <CreditsDetails
            currentUser={this.props.user.current}
            {...this.props.credits}
          />
        </div>
      </div>
    )
  }
}
