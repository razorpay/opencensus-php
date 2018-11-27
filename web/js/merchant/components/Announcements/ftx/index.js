import { connect } from 'react-redux';
import Announcement from 'merchant/components/Announcement';
import { passLink, eventsLink, visitBanner } from './ga';

@connect(state => ({ user: state.session.user }))
export default class FTXPassAnnouncement extends React.PureComponent {
  componentDidMount() {
    visitBanner();
  }

  render() {
    const user = this.props.user;

    return (
      <Announcement
        class="settlement-anc"
        theme="purply"
        title="Razorpay FTX"
        canBeClosed={false}
      >
        <div>
          <span>
            Join us for the largest Indian FinTech conference happening in
            Bengaluru on 7th Dec.
          </span>{' '}
          <a
            class="btn-link"
            target="_blank"
            href={`https://razorpay.com/events/ftx/?source=dashboard&mid=${
              user.current
            }`}
            onClick={eventsLink}
          >
            Speakers & Agenda
          </a>
          <span class="dot--primary">&#9679;</span>
          <a
            class="btn-link"
            target="_blank"
            href={`https://razorpay.typeform.com/to/SWOXx5?source=dashboard&mid=${
              user.current
            }`}
            onClick={passLink}
          >
            Claim your FREE ticket{' '}
            <i class="i i-chevron-right" style={{ fontSize: 20 }} />
          </a>
        </div>
      </Announcement>
    );
  }
}
