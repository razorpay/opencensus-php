import React from 'react';
import { connect } from 'react-redux';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { eventsLink, visitBanner } from './ga';

@connect((state) => ({ user: state.session.user }))
class FTXPassAnnouncement extends React.PureComponent {
  componentDidMount() {
    visitBanner();
  }

  render() {
    const user = this.props.user;

    return (
      <AnnouncementBanner
        class="settlement-anc"
        theme="purply"
        title="Razorpay FTX"
        canBeClosed={false}
        card_id="razorpay-ftx-banner"
      >
        <div>
          <span>
            Join us for the largest Indian FinTech conference happening in Bengaluru on 7th Dec.
          </span>{' '}
          <a
            class="btn-link"
            target="_blank"
            rel="noreferrer noopener"
            href={`https://razorpay.com/events/ftx/?source=dashboard&mid=${user.current}`}
            onClick={eventsLink}
          >
            Speakers & Agenda
          </a>
        </div>
      </AnnouncementBanner>
    );
  }
}

export default FTXPassAnnouncement;
