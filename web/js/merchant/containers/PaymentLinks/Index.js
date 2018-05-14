import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

import LinkList from 'merchant/containers/PaymentLinks/List';
import BatchList from 'merchant/containers/PaymentLinks/BatchList';
import BatchListNew from 'merchant/containers/PaymentLinks/BatchListNew';
import BatchUpload from 'merchant/containers/PaymentLinks/BatchUpload';

const OLD_BATCH_TAG = 'batch_import_links';
const NEW_BATCH_TAG = 'batch_import_links_v2';

@connect(state => {
  return {
    user: state.session.user,
  };
})
export default class PaymentLinksContainer extends Component {
  render() {
    const { user } = this.props;

    let tags = (user.isAuthenticated && user.tags) || [];
    tags = tags.map(t => t.toLowerCase());

    const isNewBatchEnabled = tags.includes(NEW_BATCH_TAG);
    const isOldBatchEnabled = tags.includes(OLD_BATCH_TAG);

    return (
      <tabbed-container>
        <header id="link-header">
          <NavLink exact to="/paymentlinks">
            Payment Links
          </NavLink>
          <ShowWhen
            myRole="owner manager operations admin"
            featureEnabled={[OLD_BATCH_TAG, NEW_BATCH_TAG]}
          >
            <NavLink exact to="/paymentlinks/batchuploads">
              Batch Uploads
            </NavLink>
          </ShowWhen>
        </header>

        <content>
          <Switch>
            {isOldBatchEnabled && (
              <Route
                path="/paymentlinks/batchuploads/new"
                component={BatchUpload}
              />
            )}

            {isNewBatchEnabled ? (
              <Route
                path="/paymentlinks/batchuploads"
                component={BatchListNew}
              />
            ) : (
              <Route path="/paymentlinks/batchuploads" component={BatchList} />
            )}

            <Route path="/paymentlinks" component={LinkList} />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}
