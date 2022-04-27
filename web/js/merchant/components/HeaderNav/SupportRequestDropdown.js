import React, { Component } from 'react';
import { connect } from 'react-redux';
import { STATUS } from 'merchant/views/Account/TrustedBadge/constants/data';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

import { logout, updateSession } from 'merchant/reducers/session';
import RTracking from 'react-tracking';
import { getFormattedDate, STATUSES } from 'merchant/views/TicketSupport/utils';

@RTracking(() => window.rzpQ.component('SupportRequestDropdown'))
class SupportTicketDropdown extends Component {
  handleHide = () => {
    // hide profile drop down when in hash mode
    if (location.hash.indexOf('#profile_dropdown') > -1) {
      this.props.history.replace(this.props.location.pathname);
    }
  };

  handleShow = () => {
    const { trustedBadge, tracking, user } = this.props;
    const { badgeStatus } = trustedBadge || {};
    const isRTBEnabled = badgeStatus === STATUS.YES_ELIGIBLE_LIVE;
    if (user && isRTBEnabled) {
      tracking.trackEvent(
        window.rzpQ &&
          window.rzpQ.merchantActions().interaction('RTBDashboardHomePageTagDisplayed', {
            merchantId: user?.merchant?.id,
            clickSource: 'merchant_dashboard',
          }),
      );
    }
  };

  render() {
    const { user, trustedBadge } = this.props;
    const { badgeStatus } = trustedBadge || {};
    const isRTBEnabled = badgeStatus === STATUS.YES_ELIGIBLE_LIVE;
    const TicketsByaAgent = this.props.ticketsRaisedByAgents.filter(
      (ticket) => STATUSES[ticket.status] === 'AWAITING_YOUR_REPLY',
    );

    return (
      <div>
        {TicketsByaAgent && TicketsByaAgent.length > 0 && (
          <Dropdown closeOnClick={false} onShow={this.handleShow} onHide={this.handleHide}>
            <DropdownTrigger
              className={`dropdown-toggle${
                !user.isAnnouncementTextEnabled && !user.isWhatsNewTextEnabled
                  ? ' dropdown-toggle--large-icon'
                  : ''
              }${isRTBEnabled ? ' rtb-user-dropdown' : ''}`}
            >
              <span class="support-request-dropdown-trigger">
                {' '}
                <img
                  src="https://cdn.razorpay.com/static/assets/ticket-system/circle-alert.svg"
                  alt=""
                />{' '}
                <span class="support-request-header-dropdown-title">Support Requests</span>
                {TicketsByaAgent.length ? <span>({TicketsByaAgent.length})</span> : null}{' '}
                <span className="caret" />
              </span>{' '}
            </DropdownTrigger>
            <DropdownContent>
              <div class="dropdown-menu support-dropdown">
                <div class="support-dropdwon-title">
                  Support {`${TicketsByaAgent.length > 1 ? 'Requests' : 'Request'}`}(
                  {TicketsByaAgent.length})
                </div>
                <div className="support-tickets-wrapper">
                  {TicketsByaAgent.map((supportTicket) => {
                    return (
                      <div className="support-ticket" key={supportTicket.id}>
                        <div className="support-ticket-header">
                          <div className="support-ticket-title">{supportTicket?.subject}</div>
                        </div>
                        <div className="support-ticket-description">
                          {supportTicket?.description_text}
                        </div>
                        <div className="reply-button-wrapper">
                          <a
                            target="_blank"
                            href={`/app/ticket-support/rzpind/${supportTicket.id}/${
                              supportTicket?.custom_fields?.cf_created_by || 'merchant'
                            }/conversation`}
                            className="btn btn-primary"
                            rel="noreferrer noopener"
                          >
                            Reply Now &nbsp; &gt;
                          </a>
                          <div className="text-danger reply-before-date">
                            Before {getFormattedDate(new Date(supportTicket.fr_due_by))}
                          </div>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            </DropdownContent>
          </Dropdown>
        )}
      </div>
    );
  }
}
export default connect(
  (state) => {
    return {
      ...state.session,
      ...state.config.config,
      user: state.session.user,
      isMobileResolution: state.app.isMobileResolution,
      trustedBadge: state.trustedBadge.status,
      ticketsRaisedByAgents: state.config.ticketsRaisedByAgents.data[1],
    };
  },
  { logout, closeModal, openModal, updateSession },
)(SupportTicketDropdown);
