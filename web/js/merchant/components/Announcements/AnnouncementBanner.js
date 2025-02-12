import React, { Component } from 'react';
import { connect } from 'react-redux';
import AnnouncementBanner from 'common/ui/AnnouncementBanner';
import { getItem, setItem } from 'common/utils/localStorage';
import { classList } from 'common/utils/rzp-utils';

function getBannerState(key) {
  const val = getItem(key);
  if (!val) return false; // return 'false' if key/value is 'null'
  try {
    return JSON.parse(val);
  } catch (e) {
    return false; // return 'false' if any error occurs
  }
}
class AnnouncementBannerComponent extends Component {
  state = {
    hidden: getBannerState(this.props.bannerKey),
  };

  handleClose = () => {
    if (this.props.bannerKey) {
      setItem(this.props.bannerKey, true);
      this.setState({ hidden: true });
    }
    if (this.props.handleClose) this.props.handleClose();
    if (!this.props.hidden && !this.props.handleClose) {
      this.setState({ hidden: true });
    }
  };

  render() {
    const {
      user,
      className,
      handleClose,
      canBeClosed,
      bannerKey,
      fullPage = false,
      hidden = false,
      ...props
    } = this.props;

    // If hidden or org is Axis, don't show banners
    // show banner for axis org only if shouldShowTnCBannerForAxis props is true.
    if (
      (this.props.hidden || this.state.hidden || user.isOrgAxis) &&
      !this.props.shouldShowTnCBannerForAxis
    ) {
      return null;
    }

    return (
      <div
        className={classList(
          'announcement-banner-container',
          fullPage && 'announcement-banner-container--fullpage',
        )}
      >
        <AnnouncementBanner
          className={classList('Announcement_Banner', className)}
          onClose={canBeClosed && this.handleClose}
          fullPage={fullPage}
          hidden={hidden}
          {...props}
        >
          {this.props.children}
        </AnnouncementBanner>
      </div>
    );
  }
}

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

export default connect(mapStateToProps, null)(AnnouncementBannerComponent);
