import React, { Component } from 'react';
import { connect } from 'react-redux';
import { fetchCreditBalance } from 'merchant/reducers/credits';
import { openModal } from 'merchant_common/reducers/modals';
import CreditsDetails from 'merchant/views/Account/Credits/components';
import SetCreditAlert from 'merchant/views/Account/Credits/components/SetCreditAlert';
import gaTrack from './ga';

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
        trackToggleHistory={trackToggleHistory}
        onManageAlert={
          this.props.user.isAllowedEdit('credits') && this.handleManageAlert
        }
        {...this.props.credits}
      />
    );
  }
}
