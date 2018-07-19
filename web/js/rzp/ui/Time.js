import React, { Component } from 'react';
import moment from 'moment';

/*
 * @props
 * value {Number} seconds since Unix Epoch
 * format {String} formats supported by moment
 * relative {Boolean} if passed display text
 *   will be relative to current time, and also
 *   autoupdates the text
 */

class Time extends Component {
  constructor(props) {
    super(props);

    const { value = moment(), format = 'DD MMM YYYY', ...otherProps } = props;

    const isRelative = (this.isRelative = 'relative' in otherProps);

    const date =
      typeof value === 'string' ? new moment(value) : moment.unix(value); // value could be of format = 2018-06-15T11:04:45Z

    this.state = {
      date,
      displayText: isRelative ? date.fromNow() : date.format(format),
    };

    this.timer = null;
  }

  getUpdateInterval(date) {
    const diff = moment().diff(date);

    // if diff less than a hour, update every minute
    if (diff < 60 * 60 * 1000) {
      return 60 * 1000;
    }

    // update every hour
    return 24 * 60 * 60;
  }

  componentDidMount() {
    if (!this.isRelative) {
      return;
    }

    const { date } = this.state;

    this.timer = window.setInterval(() => {
      this.setState({
        displayText: date.fromNow(),
      });
    }, this.getUpdateInterval(date));
  }

  componentWillUnmount() {
    window.clearInterval(this.timer);
  }

  render() {
    const { date, displayText } = this.state,
      { format, value, relative, ...props } = this.props,
      isoString = date.toISOString(),
      title = date.toDate() + '';

    if (!value) {
      return '--';
    }

    return (
      <time dateTime={`${isoString}`} title={`${title}`} {...props}>
        {displayText}
      </time>
    );
  }
}

export default Time;
