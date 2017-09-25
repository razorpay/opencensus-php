import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { findBy } from 'rzp/utils/rzp-utils';

function convertToArray(arrayOrString) {
  if (arrayOrString) {
    return arrayOrString instanceof Array ? arrayOrString : [arrayOrString];
  }

  return arrayOrString;
}

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

    let isContentVisible = false;

    if (user.isAuthenticated) {
      userRole = user.userRole;
    }

    /*
     * Show content when
     * - both feature or apiFeature does not exist (0)
     * - feature or apiFeature exists and is enabled for merchabt (1)
     * - user role has access to component (2)
     *
     * Don't show when
     * - feature or apiFeature key exists but is not enabled for merchant (3)
     * - user role does not have access to component (4)
     *
     * Numbers after the item represent the priority of the item
     */

    apiFeatureEnabled = convertToArray(apiFeatureEnabled);
    featureEnabled = convertToArray(featureEnabled);

    if (!apiFeatureEnabled && !featureEnabled) {
      isContentVisible = true;
    } else if (
      apiFeatureEnabled &&
      apiFeatureEnabled.some(r => user.isFeatureEnabled(r))
    ) {
      isContentVisible = true;
    } else if (
      featureEnabled &&
      featureEnabled.some(r => tags.includes(r.toLowerCase()))
    ) {
      isContentVisible = true;
    }

    if (
      (myRole && myRoles.indexOf(userRole) === -1) ||
      (notMyRole && notMyRoles.indexOf(userRole) !== -1)
    ) {
      isContentVisible = false;
    }

    return isContentVisible ? children : null;
  }
}
