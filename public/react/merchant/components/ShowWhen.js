import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';

@connect(state => state.session, null)
export default class ShowWhen extends Component {
  render() {
    let { notMyRole = '', myRole = '', children, featureEnabled } = this.props;

    if (myRole && notMyRole) {
      throw new Error(
        "myRole and notMyRole can't coexist for component ShowWhen"
      );
    }

    let myRoles = myRole.split(' ');
    let notMyRoles = notMyRole.split(' ');
    let user = this.props.user;
    let tags = (user && user.tags) || [];
    let userRole;

    if (user) {
      userRole = user.merchants[user.id].pivot.role;
    }

    if (
      (myRole && myRoles.indexOf(userRole) === -1) ||
      (notMyRole && notMyRoles.indexOf(userRole) !== -1)
    ) {
      return null;
    }

    if (featureEnabled && tags.indexOf(featureEnabled) === -1) {
      return null;
    }

    return children;
  }
}
