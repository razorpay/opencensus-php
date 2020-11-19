import React, { Component } from 'react';

import AnnouncementBanner from 'common/ui/AnnouncementBanner';
import LocalStorageService from 'common/utils/localStorage';

import { classList } from 'common/utils/rzp-utils';

export default class extends Component {
  constructor(props) {
    super(props);

    this.state = {
      hidden: !!LocalStorageService.getItem(this.props.bannerKey),
    };

    this.handleClose = this.handleClose.bind(this);
  }

  handleClose() {
    if (this.props.bannerKey) {
      LocalStorageService.setItem(this.props.bannerKey, 1);
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
      className,
      handleClose,
      canBeClosed,
      bannerKey,
      fullPage = false,
      ...props
    } = this.props;

    if (this.props.hidden || this.state.hidden) {
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
