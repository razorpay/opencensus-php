import React, { Component } from 'react';
import { NavLink } from 'react-router-dom';
import ShowWhen from 'merchantLA/components/ShowWhen';
import { withRouter } from 'common/deprecated/withRouter';

class MyAccount extends Component {
  render() {
    const { children } = this.props;
    return (
      <tabbed-container>
        {/* To make the header scrollable we just need to add this new class to the header component */}
        <header id="myaccount-header" className="scrollable-tab-header">
          <NavLink to="/profile">Profile</NavLink>
          <ShowWhen myRole="linked_account_owner">
            <NavLink to="/team">Manage Team</NavLink>
          </ShowWhen>
        </header>
        <content>{children}</content>
      </tabbed-container>
    );
  }
}

export default withRouter(MyAccount);
