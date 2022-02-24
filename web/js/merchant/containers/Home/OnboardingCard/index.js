import React, { Component } from 'react';
import { connect } from 'react-redux';

import { fetchKeys } from 'merchant/reducers/keys';

import { LIVE_MODE } from './data';
import InstantActivationsCard from './Instant';
import RegularActivationsCard from './Regular';

@connect(state => ({ user: state.session.user, mode: state.session.mode }), {
  fetchKeys,
})
export default class OnboardingCard extends Component {
  constructor(props) {
    super(props);

    const { mode, payments, user } = props;

    this.state = {
      integration: {
        isLoading: true,
        keysGenerated: false,
        paymentsMade: false,
        isKLA: !user.has_key_access,
      },
    };

    this.paymentsRequest = new Promise((res, rej) => {
      this.onFetchPayments = res;

      if (!props.payments.loading) {
        res(props.payments.items);
      }
    });
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.payments.loading && !nextProps.payments.loading) {
      this.onFetchPayments(nextProps.payments.items);
    }
  }

  UNSAFE_componentWillMount() {
    let params = {};

    const { user, mode } = this.props,
      isKLA = !user.has_key_access;

    params.mode = this.props.mode;

    Promise.all([
      (mode === LIVE_MODE && isKLA && Promise.resolve(false)) ||
        this.props.fetchKeys(params, user.has_key_access).then(({ data }) => {
          return !!data.items.length;
        }),
      this.paymentsRequest.then(payments => {
        return !!payments.length;
      }),
    ]).then(resp => {
      const { 0: keysGenerated, 1: paymentsMade } = resp;

      this.setState({
        integration: {
          isLoading: false,
          keysGenerated,
          paymentsMade,
          isKLA,
        },
      });
    });
  }

  render() {
    const { showInstantActivation, ...rest } = this.props,
      props = { integration: this.state.integration, ...rest };

    return showInstantActivation ? (
      <InstantActivationsCard {...props} />
    ) : (
      <RegularActivationsCard {...props} />
    );
  }
}
