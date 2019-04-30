import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';
import PaymentLinksList from 'merchant/containers/PaymentLinks/Links/List';
import BatchUploadList from 'merchant/containers/PaymentLinks/BatchUpload/List';

import TestModeBanner from 'merchant/containers/TestModeBanner';
import Button from 'component/Button';
import { ShowWhenRoute } from 'merchant/components/ShowWhen';

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
          <ShowWhen
            additionalCondition={user =>
              user.isAllowedView('payment_links_batch_uploads')
            }
          >
            <NavLink exact to="/paymentlinks/batchuploads">
              Batch Uploads
            </NavLink>
          </ShowWhen>
        </header>

        <TestModeBanner />

        <content>
          <Switch>
            <ShowWhenRoute
              path="/paymentlinks/batchuploads"
              component={BatchUploadList}
              additionalCondition={user =>
                user.isAllowedView('payment_links_batch_uploads')
              }
            />
            <Route path="/paymentlinks" component={PaymentLinksList} />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}
