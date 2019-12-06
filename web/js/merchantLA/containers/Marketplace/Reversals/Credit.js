import { Component } from 'react';
import { connect } from 'react-redux';
import { fetchCreditBalance } from 'merchantLA/reducers/credits';
import CreditsDetails from 'merchant/views/Account/Credits/components';

import gaTrack from './ga';

const { trackToggleHistory } = gaTrack('LA Dashboard - Reversals Credits');

@connect(
  state => {
    return {
      credits: {
        ...state.credits,
        creditsData: state.credits.creditsData.data,
        balanceData: state.credits.balanceData.data,
      },
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
        showDocumentation={false}
        {...this.props.credits}
      />
    );
  }
}
