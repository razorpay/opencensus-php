import React, { Component } from 'react';
import moment from 'moment';

const getTimeAgo = time => moment(time * 1000).fromNow();

class LastUpdated extends Component {
  constructor({ at }) {
    super();
    this.state = {
      timeAgo: getTimeAgo(at),
    };
  }

  componentDidMount() {
    this.timer = setInterval(() => {
      this.setState({
        timeAgo: getTimeAgo(this.props.at),
      });
    }, 1000);
  }

  render() {
    return (
      <small>
        <i className="icon icon-info-circle" />&nbsp;
        <span>
          The graph data last updated {this.state.timeAgo}
        </span>
      </small>
    );
  }

  componentWillUnmount() {
    clearInterval(this.timer);
  }
}

export default LastUpdated;
