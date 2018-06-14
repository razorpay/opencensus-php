import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

import LinkList from 'merchant/containers/PaymentLinks/List';
import BatchListNew from 'merchant/containers/PaymentLinks/BatchListNew';
import TestModeBanner from 'merchant/containers/TestModeBanner';

import Button from 'component/Button';

import LocalStorageService from 'rzp/utils/localStorage';
import { classList } from 'common/util';
import createEvent from 'rzp/utils/event';

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
            <Route path="/paymentlinks/batchuploads" component={BatchListNew} />

            <Route path="/paymentlinks" component={LinkList} />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}

/*
* USAGE:
*

<AnnouncementBanner
  handleClick={this.handleAnnouncementClose}
  hidden={!this.state.showAnnouncementBanner}
  content={
    <span>
      Issuing hundreds of payment links manually? Instead, upload an excel sheet and leave the rest to us. Try our{' '}
      <NavLink
        class="link"
        to="/paymentlinks/batchuploads"
        onClick={trackLinkClick}
      >
        Batch Uploads
      </NavLink>.
    </span>
  }
/>

*
* */
const AnnouncementBanner = ({ className, hidden, content, handleClick }) => {
  return (
    <div
      class={classList(
        'Announcement_Banner',
        className,
        hidden && 'Announcement_Banner--hide'
      )}
    >
      {content}
      <Button.Transparent class="close-btn" onClick={handleClick}>
        ×
      </Button.Transparent>
    </div>
  );
};
