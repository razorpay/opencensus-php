import React, { Component } from 'react';
import { connect } from 'react-redux';
import { fetchCreditBalance } from 'merchant/modules/credits';
import CreditsDetails from 'merchant/components/Credits';

@connect(
  state => {
    return {
      credits: state.credits,
      user: state.session.user,
    };
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
      <CreditsDetails
        currentUser={this.props.user.current}
        {...this.props.credits}
      />
    );
  }
}
