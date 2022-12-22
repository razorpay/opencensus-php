import React, { Component } from 'react';
import moment from 'moment';
import omit from 'lodash/omit';

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

    this.state = this.getInitTime(props);

    this.timer = null;
  }

  getUpdateInterval(date) {
    const diff = moment().diff(date);

    // if diff less than a hour, update every minute
    if (diff < 60 * 60 * 1000) return 60 * 1000;
    // update every hour
    return 24 * 60 * 60;
  }

  componentDidMount() {
    if (!this.isRelative) return;

    const { date } = this.state;
    const { timerCallBack, id } = this.props;

    this.timer = window.setInterval(() => {
      this.setState({ displayText: date.fromNow() });
      timerCallBack?.(date, id);
    }, this.getUpdateInterval(date));
  }

  componentWillUnmount() {
    window.clearInterval(this.timer);
  }

  getInitTime = (props = this.props) => {
    const { value = moment().local(), format = 'DD MMM YYYY', ...otherProps } = props;
    const isRelative = 'relative' in otherProps;
    this.isRelative = isRelative;

    const date = typeof value === 'string' ? moment(value) : moment.unix(value); // value could be of format = 2018-06-15T11:04:45Z

    return {
      date,
      displayText: isRelative ? date.fromNow() : date.format(format),
    };
  };

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (nextProps.value !== this.props.value) {
      const data = this.getInitTime(nextProps);

      this.setState(data);
    }
  }

  render() {
    const { date, displayText } = this.state;
    const { format, value, relative, ...props } = this.props;
    const isoString = date.toISOString();
    const title = `${date.local().toDate()}`;

    if (!value) return '--';

    return (
      <time dateTime={`${isoString}`} title={`${title}`} {...omit(props, 'timerCallBack')}>
        {displayText}
      </time>
    );
  }
}

export default Time;
