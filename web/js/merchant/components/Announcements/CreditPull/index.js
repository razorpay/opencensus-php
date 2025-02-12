import React, { Component } from 'react';
import CreditPullModal from 'merchant/containers/CreditPullModal';
import { connect } from 'react-redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { compose } from 'redux';

class CreditPullAnnouncement extends Component {
  render() {
    return (
      <AnnouncementBanner
        theme="primary"
        title="Free Credit Score!"
        canBeClosed={true}
        card_id="free-credit-score-banner"
      >
        <span>
          Click here to check your credit report for FREE!{` `}
          <a
            onClick={() => {
              this.props.openModal({
                component: <CreditPullModal fromWhere="Home Announcement Banner" />,
                size: 'regular',
              });
            }}
          >
            Check Credit Score
          </a>
        </span>
      </AnnouncementBanner>
    );
  }
}

export default compose(
  connect(() => ({}), {
    ...ModalActions,
  }),
)(CreditPullAnnouncement);
