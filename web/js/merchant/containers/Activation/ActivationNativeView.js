import React from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import * as EventActions from 'merchant/reducers/trackEvents';

import ActivationForm from './index';

class ActivationNativeView extends React.Component {
  componentDidMount() {
    if (this.props.user?.isActivationFormFullView) {
      this.props.trackEvents({
        objectName: 'native full view activation form',
        actionName: 'displayed',
        screen: 'KYC Document',
        properties: {
          show_activation_form_full_view: 'true',
          experiment_name: 'show_activation_form_full_view',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
  }

  render() {
    return <ActivationForm />;
  }
}

export default compose(
  connect(
    (state) => ({
      session: state.session,
      user: state.session.user,
    }),
    {
      ...EventActions,
    },
  ),
)(ActivationNativeView);
