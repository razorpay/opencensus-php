import React, { Component } from 'react';
import { connect } from 'react-redux';
import { fetchCreditBalance } from 'merchant/modules/credits';
import { openModal } from 'rzp/modules/modals';
import CreditsDetails from 'merchant/components/Credits';

import SetCreditAlert from './SetCreditAlert';

@connect(
  state => {
    return {
      credits: state.credits,
      user: state.session.user,
    };
  },
  { fetchCreditBalance, openModal }
)
export default class CreditsListContainer extends Component {
  componentDidMount() {
    if (this.props.user.current) {
      this.props.fetchCreditBalance();
    }
  }

  handleManageAlert = () => {
    this.props.openModal({
      size: 'small',
      component: <SetCreditAlert />,
    });
  };

  render() {
    return (
      <CreditsDetails
        currentUser={this.props.user.current}
        onManageAlert={this.handleManageAlert}
        {...this.props.credits}
      />
    );
  }
}
