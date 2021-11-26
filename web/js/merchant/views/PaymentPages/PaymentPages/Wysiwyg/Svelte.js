import React from 'react';
import { connect } from 'react-redux';
@connect((state) => ({ user: state.session.user, org: state.session.org }))
export default class Svelte extends React.Component {
  shouldComponentUpdate() {
    return false; // No need to re-render again, all 3 React apps are working independently bridged via store
  }

  initialize = (node) => {
    if (!node) return;

    const { payment_page_id, user, org } = this.props;

    this.templateData = {
      is_test_mode: this.props.isTestMode,
      merchant: this.props.merchantData,
      context: {
        page_title: payment_page_id
          ? `Edit Payment Page - ${payment_page_id}`
          : 'Create New Payment Page',
        form_title: 'Payment Details',
        isWYSIWYGMode: true,
      },
      org: {
        branding: {
          branding_logo: user.isOrgRZP ? null : org.payment_apps_logo_url,
          show_rzp_logo: user.isOrgRZP,
        },
        custom_code: org.custom_code,
      },
      // Other keys are not required by Svelte app in isWYSIWYGMode
    };

    console.log(this.props.user.isWhiteLabelledOrg, this.templateData);

    this._svelteInstance = window.RZP.renderApp(node, this.templateData);
  };

  componentWillUnmount() {
    // eslint-disable-next-line babel/no-unused-expressions
    this._svelteInstance && this._svelteInstance.destroy();
  }

  // componentWillReceiveProps() {
  //   // const newData = {}; // Update application data
  //   // this._svelteInstanceinstance.set(newData);
  // }

  componentDidMount() {
    this.props.onMount();
  }

  render() {
    // Setting the root
    return React.createElement('div', {
      ref: this.initialize,
      id: 'wysiwyg-root',
    });
  }
}
