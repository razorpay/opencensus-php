import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { findBy } from 'rzp/utils/rzp-utils';

@connect(state => {
  return {
    ...state.session,
    features: state.config.features,
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
    let features = (user.isAuthenticated && this.props.features) || [];
    tags = tags.map(tag => tag.toLowerCase());
    let userRole;

    if (user.isAuthenticated) {
      userRole = user.userRole;
    }

    if (
      (myRole && myRoles.indexOf(userRole) === -1) ||
      (notMyRole && notMyRoles.indexOf(userRole) !== -1)
    ) {
      return null;
    }

    if (apiFeatureEnabled) {
      let feature = findBy(
        features,
        'feature',
        apiFeatureEnabled.toLowerCase()
      );

      if (feature && !feature.value) {
        return null;
      }
    }

    if (featureEnabled) {
      if (featureEnabled instanceof Array) {
        // Array elements will be matched to tags as per `OR` and not `AND`
        if (!featureEnabled.some(r => tags.includes(r.toLowerCase()))) {
          return null;
        }
      } else if (tags.indexOf(featureEnabled.toLowerCase()) === -1) {
        return null;
      }
    }

    return children;
  }
}
