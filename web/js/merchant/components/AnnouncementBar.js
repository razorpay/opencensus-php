import React, { Component } from 'react';
import RTracking from 'react-tracking';

@RTracking(() => window.rzpQ.component('ScheduledNitroBanner'))
export default class AnnouncementBar extends Component {
  constructor(props) {
    super(props);
  }

  componentDidMount() {
    const tracking = this.props.tracking;
    tracking.trackEvent(
      window.rzpQ.merchantActions().success(`${this.props.fromWhere}_display_promo_notification2`),
    );
  }

  trackEvents = (fromWhere) => {
    const tracking = this.props.tracking;

    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated(`${fromWhere}_click_promo_notification2_cta1`),
    );
  };

  render() {
    return (
      <div class="announcement-sidebar">
        <div class="wrapper">
          <h2>Reduce Transaction Fees to 1.65% </h2>
          <p>Get a current Account with RazorpayX</p>
        </div>
        <a
          class="Button--secondary Button scheduled-btn-act btn-border"
          href={this.props.url}
          target="_blank"
          onClick={(e) => {
            this.trackEvents(this.props.fromWhere);
          }}
        >
          Learn More
        </a>
      </div>
    );
  }
}
