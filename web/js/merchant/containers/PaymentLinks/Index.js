import React, { Component } from 'react';
import { Route, Switch, NavLink } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

import LinkList from 'merchant/containers/PaymentLinks/List';
import BatchList from 'merchant/containers/PaymentLinks/BatchList';
import BatchUpload from 'merchant/containers/PaymentLinks/BatchUpload';

export default class PaymentLinksContainer extends Component {
  render() {
    return (
      <tabbed-container>
        <header id="link-header">
          <NavLink exact to="/paymentlinks">
            Payment Links
          </NavLink>
          <ShowWhen
            myRole="owner manager operations admin"
            featureEnabled="batch_import_links"
          >
            <NavLink exact to="/paymentlinks/batchuploads">
              Batch Uploads
            </NavLink>
          </ShowWhen>
        </header>

        <content>
          <Switch>
            <Route path="/paymentlinks/batchuploads" component={BatchList} />
            <Route path="/paymentlinks" component={LinkList} />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}
