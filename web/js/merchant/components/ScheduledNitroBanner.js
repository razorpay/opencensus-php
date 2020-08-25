import React, { Component } from 'react';
import RTracking from 'react-tracking';

@RTracking(() => window.rzpQ.component('ScheduledNitroBanner'))
export default class ScheduledNitroBanner extends Component {
  constructor(props) {
    super(props);
  }

  componentDidMount() {
    const tracking = this.props.tracking;
    tracking.trackEvent(
      window.rzpQ
        .merchantActions()
        .success(`${this.props.fromWhere}.display_promo_notification`)
    );
  }

  trackEvents = (fromWhere) => {
    const tracking = this.props.tracking;

    tracking.trackEvent(
      window.rzpQ
        .merchantActions()
        .initiated(`${fromWhere}.click_promo_notification_cta1`)
    );
  };

  render() {
    return (
      <div class="pull-right schedule-enable-container">
        <i class="i i-early-settlement scheduled-enable" />
        Reduce transaction fee to 1.65% with a RazorpayX Current Account
        <a
          class="Button--secondary Button scheduled-btn-act btn-border"
          href={this.props.url}
          target="_blank"
          onClick={e => {
            this.trackEvents(this.props.fromWhere);
          }}
        >
          Learn More
        </a>
      </div>
    );
  }
}
