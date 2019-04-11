import { Component } from 'react';
import { connect } from 'react-redux';
import { fetchCreditBalance } from 'merchantLA/modules/credits';
import CreditsDetails from 'merchant/components/Credits';

import gaTrack from './ga';

const { trackForm, trackToggleHistory } = gaTrack('Dashboard - Credits');

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
        trackToggleHistory={trackToggleHistory}
        {...this.props.credits}
      />
    );
  }
}
