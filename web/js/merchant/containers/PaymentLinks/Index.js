import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';
import PaymentLinksList from 'merchant/containers/PaymentLinks/Links/List';
import BatchUploadList from 'merchant/containers/PaymentLinks/BatchUpload/List';

import TestModeBanner from 'merchant/containers/TestModeBanner';
import Button from 'component/Button';

import { classList } from 'common/util';

@connect(state => {
  return {
    user: state.session.user,
  };
})
export default class PaymentLinksContainer extends Component {
  render() {
    const { user } = this.props;

    return (
      <tabbed-container>
        <header id="link-header">
          <NavLink exact to="/paymentlinks">
            Payment Links
          </NavLink>
          <ShowWhen myRole="owner manager operations admin">
            <NavLink exact to="/paymentlinks/batchuploads">
              Batch Uploads
            </NavLink>
          </ShowWhen>
        </header>

        <TestModeBanner />

        <content>
          <Switch>
            <Route
              path="/paymentlinks/batchuploads"
              component={BatchUploadList}
            />
            <Route path="/paymentlinks" component={PaymentLinksList} />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}
