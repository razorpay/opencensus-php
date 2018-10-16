import React, { Component } from 'react';

import AnnouncementBanner from 'rzp/ui/AnnouncementBanner';
import LocalStorageService from 'rzp/utils/localStorage';

import { classList } from 'common/util';

export default class Announcement extends Component {
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
      ...props
    } = this.props;

    return (
      <AnnouncementBanner
        className={classList(
          'Announcement_Banner',
          className,
          (this.props.hidden || this.state.hidden) &&
            'Announcement_Banner--hide'
        )}
        onClose={canBeClosed && this.handleClose}
        {...props}
      >
        {this.props.children}
      </AnnouncementBanner>
    );
  }
}
