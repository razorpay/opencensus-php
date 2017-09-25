import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { findBy } from 'rzp/utils/rzp-utils';

@connect(state => {
  return {
    ...state.session,
  };
}, null)
export default class ShowWhen extends Component {
  render() {
    let {
      notMyRole = '',
      myRole = '',
      children,
      featureEnabled,
      apiFeatureEnabled,
    } = this.props;

    if (myRole && notMyRole) {
      throw new Error(
        "myRole and notMyRole can't coexist for component ShowWhen"
      );
    }

    let myRoles = myRole.split(' ');
    let notMyRoles = notMyRole.split(' ');
    let user = this.props.user;
    let tags = (user.isAuthenticated && user.tags) || [];
    let features = (user.isAuthenticated && user.features) || [];
    tags = tags.map(tag => tag.toLowerCase());
    let userRole;

    let isContentVisible = true;

    if (user.isAuthenticated) {
      userRole = user.userRole;
    }

    if (
      (myRole && myRoles.indexOf(userRole) === -1) ||
      (notMyRole && notMyRoles.indexOf(userRole) !== -1)
    ) {
      isContentVisible = false;
    }

    if (apiFeatureEnabled) {
      if (apiFeatureEnabled instanceof Array) {
        // Array elements will be matched to tags as per `OR` and not `AND`
        if (
          !apiFeatureEnabled.some(r => {
            return user.isFeatureEnabled(r);
          })
        ) {
          isContentVisible = false;
        }
      } else if (!user.isFeatureEnabled(apiFeatureEnabled)) {
        isContentVisible = false;
      }
    }

    if (!isContentVisible && featureEnabled) {
      if (featureEnabled instanceof Array) {
        // Array elements will be matched to tags as per `OR` and not `AND`
        if (!featureEnabled.some(r => tags.includes(r.toLowerCase()))) {
          isContentVisible = false;
        }
      } else if (tags.indexOf(featureEnabled.toLowerCase()) === -1) {
        isContentVisible = false;
      }
    }

    return isContentVisible ? children : null;
  }
}
