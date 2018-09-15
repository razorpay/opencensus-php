import React, { Component } from 'react';
import Button from 'component/Button';
import { classList } from 'common/util';
import ShowWhen from 'merchant/components/ShowWhen';
import { connect } from 'react-redux';
import * as ModalActions from 'rzp/modules/modals';
import LocalStorageService from 'rzp/utils/localStorage';
import RequestEarlyAccessForm from './EarlySettlementsModal';

export default class Announcement extends Component {
  constructor(props) {
    super();
    this.props = props;
  }

  handleClose = () => {
    if (this.props.handleClose) {
      this.props.handleClose();
    }
  };

  render() {
    return (
      <div
        class={classList(
          'Announcement_Banner_2',
          this.props.className,
          this.props.hidden && 'Announcement_Banner--hide'
        )}
      >
        {this.props.children}
        <Button.Transparent class="close-btn" onClick={this.handleClose}>
          ×
        </Button.Transparent>
      </div>
    );
  }
}

@connect(
  state => ({ user: state.session.user }),
  { ...ModalActions }
)
export class EarlySettlementAnnouncement extends Component {
  constructor(props) {
    super();
    this.state = {};
    this.state.bannerKey = `early-settlement-banner-viewed-${props.user.id}`;
    if (props.user.findTag('early_settlements_available')) {
      this.state.isHidden = LocalStorageService.getItem(this.state.bannerKey);
    } else {
      this.state.isHidden = true;
    }

    this.handleClose = this.handleClose.bind(this);
    this.handleRequest = this.handleRequest.bind(this);
  }

  handleClose() {
    this.setState({
      isHidden: true,
    });

    LocalStorageService.setItem(this.state.bannerKey, 1);
  }

  handleRequest() {
    this.props.openModal({
      component: <RequestEarlyAccessForm closeBanner={this.handleClose} />,
      size: 'small',
    });
  }

  render() {
    return (
      <ShowWhen myRole="owner manager admin">
        <Announcement
          class="settlement-anc"
          hidden={this.state.isHidden}
          handleClose={this.handleClose}
        >
          <div>
            <div class="title">
              <span>Introducing Early Settlements</span>
            </div>
            <div class="corner" />
            <div class="content">
              <span>
                Get your payments within 12 working hours and never have a
                shortfall of working capital.
              </span>
              <Button.Transparent class="btn-link" onClick={this.handleRequest}>
                Request Access <i class="i-chevron-right" />
              </Button.Transparent>
            </div>
          </div>
        </Announcement>
      </ShowWhen>
    );
  }
}
