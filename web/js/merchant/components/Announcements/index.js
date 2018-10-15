import React, { Component } from 'react';
import Button from 'component/Button';
import { classList } from 'common/util';
import ShowWhen from 'merchant/components/ShowWhen';
import { connect } from 'react-redux';
import * as ModalActions from 'rzp/modules/modals';
import LocalStorageService from 'rzp/utils/localStorage';
import RequestEarlyAccessForm from './EarlySettlementsModal';
import trackESAnnouncements from './ga';

export default class Announcement extends Component {
  constructor(props) {
    super();
    this.state = {
      hidden: false,
    };
  }

  handleClose = () => {
    if (this.props.bannerKey) {
      LocalStorageService.setItem(this.props.bannerKey, 1);
    }

    if (this.props.handleClose) {
      this.props.handleClose();
    }

    this.setState({
      hidden: true,
    });
  };

  render() {
    return (
      <div
        class={classList(
          'Announcement_Banner',
          this.props.className,
          (this.props.hidden || this.state.hidden) &&
            'Announcement_Banner--hide'
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
    this.state = {
      bannerKey: `early-settlement-banner-viewed-${props.user.current}`,
    };

    if (props.user.showEarlySettlementAnnouncement) {
      this.state.isHidden = LocalStorageService.getItem(this.state.bannerKey);
    } else {
      this.state.isHidden = true;
    }

    this.handleCloseButton = this.handleCloseButton.bind(this);
    this.closeBanner = this.closeBanner.bind(this);
    this.handleRequest = this.handleRequest.bind(this);
  }

  componentDidMount() {
    if (!this.state.isHidden) {
      trackESAnnouncements.earlySettlementAppear(this.props.from);
    }
    window.addEventListener('remove-es-announcement', this.closeBanner, false);
  }

  removeCustomEvent() {
    window.removeEventListener(
      'remove-es-announcement',
      this.closeBanner,
      false
    );
  }

  handleCloseButton() {
    this.closeBanner();
    trackESAnnouncements.earlySettlementClickCloseButton(this.props.from);
  }

  closeBanner() {
    this.setState({
      isHidden: true,
    });

    LocalStorageService.setItem(this.state.bannerKey, 1);
    this.removeCustomEvent();
  }

  componentWillUnmount() {
    this.removeCustomEvent();
  }

  handleRequest() {
    trackESAnnouncements.earlySettlementClickRequestAccess(this.props.from);
    this.props.openModal({
      component: (
        <RequestEarlyAccessForm
          closeBanner={this.closeBanner}
          from={this.props.from}
        />
      ),
      size: 'large',
    });
  }

  render() {
    let className = classList(
      'settlement-anc',
      this.props.withTour && 'with-tour',
      this.props.marginBottom && 'margin-bottom'
    );

    return (
      <ShowWhen additionalCondition={user => user.isAllowedView('settlements')}>
        <Announcement
          class={className}
          hidden={this.state.isHidden}
          handleClose={this.handleCloseButton}
          bannerKey={this.state.bannerKey}
        >
          <div>
            <div class="title">
              <span>Introducing Early Settlements</span>
            </div>

            <div class="corner" />

            <div class="content">
              <span>
                Get your payments settled within <strong>a few hours</strong>{' '}
                and never have a shortfall of working capital.&nbsp;
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
