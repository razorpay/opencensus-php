import 'common/polyfill';
import React, { Component } from 'react';
import { Link, withRouter } from 'react-router-dom';
import Content, { Sidebar } from 'admin/Content';

import ModalContainer from 'common/modal';
import ErrorBoundary from 'common/ErrorBoundary';

import user from 'admin/user';

import AsyncButton from 'ui/AsyncButton';

import fetch from 'common/fetch';

@withRouter
export default class App extends Component {
  /*
    Following urls will be redirected to
     - http://dashboard.razorpay.com/admin#/app/entity/live/pricing_plan/8DayWD4r6ewju2
     TO
     http://dashboard.razorpay.com/admin/entity/live/pricing_plan/8DayWD4r6ewju2
     - http://dashboard.razorpay.com/admin#/app/merchants/9FI02gqNcWhPxy/detail
     TO
     http://dashboard.razorpay.com/admin/merchants/9FI02gqNcWhPxy/detail
  */
  componentWillUpdate() {
    this.legacyUrlSupport();
  }

  componentWillMount() {
    this.legacyUrlSupport();
  }

  legacyUrlSupport() {
    let hashUrl = this.props.location.hash;
    if (hashUrl && hashUrl.indexOf('#/') > -1) {
      hashUrl = hashUrl.substring(2); // Remove '#/'
      hashUrl = hashUrl.replace('app', ''); // Remove 'app'
      this.props.history.replace(hashUrl);
    }
  }

  handleLogout = () => {
    return fetch({
      url: '/admin/user/logout',
    }).then(r => {
      window.location.reload();
    });
  };

  render() {
    return (
      <div class="app-container">
        <main>
          <ErrorBoundary resetOnProps location={this.props.location}>
            <Content {...this.props} />
          </ErrorBoundary>
        </main>
        <header>
          <div id="profile-icon">
            {user.name}
            <i class="i-arrow-down" />
            <div class="menu">
              <Link to="/profile">
                <i class="i-user" />
                Profile
              </Link>
              <AsyncButton
                onClick={this.handleLogout}
                class="logout-btn btn-default"
                pendingClass="logout-btn btn-default btn-pending"
              >
                <i class="i-logout" />
                Logout
                <span class="spin-btn" />
              </AsyncButton>
            </div>
          </div>
        </header>
        <Sidebar />;
        <ModalContainer />
      </div>
    );
  }
}
