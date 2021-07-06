import React, { Component } from 'react';
import { connect } from 'react-redux';
import AnnouncementBanner from 'common/ui/AnnouncementBanner';
import { getItem, setItem } from 'common/utils/localStorage';
import { classList } from 'common/utils/rzp-utils';

class AnnouncementBannerComponent extends Component {
  constructor(props) {
    super(props);

    this.state = {
      hidden: !!getItem(this.props.bannerKey),
    };

    this.handleClose = this.handleClose.bind(this);
  }

  handleClose() {
    if (this.props.bannerKey) {
      setItem(this.props.bannerKey, 1);
      this.setState({
        hidden: true,
      });
    }

    if (this.props.handleClose) {
      this.props.handleClose();
    }
  }

  render() {
    const {
      user,
      className,
      handleClose,
      canBeClosed,
      bannerKey,
      fullPage = false,
      ...props
    } = this.props;

    // If hidden or org is Axis, don't show banners
    if (this.props.hidden || this.state.hidden || user.isOrgAxis) {
      return null;
    }

    return (
      <div
        class={classList(
          'announcement-banner-container',
          fullPage && 'announcement-banner-container--fullpage',
        )}
      >
        <AnnouncementBanner
          class={classList('Announcement_Banner', className)}
          onClose={canBeClosed && this.handleClose}
          fullPage={fullPage}
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
