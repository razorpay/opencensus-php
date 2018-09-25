import React, { Component } from 'react';
import { connect } from 'react-redux';
import { fetchCreditBalance } from 'merchant/modules/credits';
import { openModal } from 'rzp/modules/modals';
import CreditsDetails from 'merchant/components/Credits';
import gaTrack from './ga';

import SetCreditAlert from './SetCreditAlert';

const { trackForm, trackToggleHistory } = gaTrack('Dashboard - Credits');
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
      component: <SetCreditAlert trackForm={trackForm('Fee Credits')} />,
    });
  };

  render() {
    return (
      <CreditsDetails
        currentUser={this.props.user.current}
        onManageAlert={this.handleManageAlert}
        trackToggleHistory={trackToggleHistory}
        {...this.props.credits}
      />
    );
  }
}
