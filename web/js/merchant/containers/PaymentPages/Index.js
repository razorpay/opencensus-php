import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import PaymentPagesList from './Pages/List';

import TestModeBanner from 'merchant/containers/TestModeBanner';
import Button from 'component/Button';

import { classList } from 'common/util';

@connect(state => {
  return {
    user: state.session.user,
  };
})
export default class PaymentPagesContainer extends Component {
  render() {
    const { user } = this.props;

    return (
      <tabbed-container>
        <header id="link-header">
          <NavLink exact to="/paymentpages">
            Payment Pages
            <span
              class="badge bg-success hidden-xs"
              style={{ marginLeft: '5px' }}
            >
              new
            </span>
          </NavLink>
        </header>

        <TestModeBanner />

        <content>
          <Switch>
            <Route path="/paymentpages" component={PaymentPagesList} />
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
