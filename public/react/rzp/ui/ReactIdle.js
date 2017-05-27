import { Component } from 'react';

export default class ReactIdle extends Component {
  events = 'mousemove keydown keypress mousewheel mousedown touchstart touchmove scroll';
  counter = 0;
  sleep = false;

  componentWillMount() {
    let events = this.events.split(' ');
    for (let i = 0, len = events.length; i < len; i++) {
      document.addEventListener(events[i], this.didIdleEnd, true);
    }
    this.startTimer();
  }

  didIdleEnd = () => {
    this.resetTimer();
    if (this.sleep) {
      this.props.onIdleEnd();
      this.sleep = false;
    }
  };

  startTimer() {
    let {
      idleDuration,
      warningDuration,
      onIdleStart,
      onIdleTimeout,
    } = this.props;
    let idleTimeout = idleDuration + warningDuration;

    this.timer = setInterval(() => {
      let counter = ++this.counter;

      if (counter > idleTimeout) {
        this.sleep = true;
        onIdleTimeout();
        this.stopTimer();
      } else if (!this.sleep && counter > idleDuration) {
        this.sleep = true;
        onIdleStart();
      }
    }, 1000);
  }

  resetTimer() {
    this.counter = 0;
  }

  stopTimer() {
    this.resetTimer();
    clearInterval(this.timer);
  }

  componentWillUnmount() {
    let events = this.events.split(' ');
    for (let i = 0, len = events.length; i < len; i++) {
      document.removeEventListener(events[i], this.resetTimer, true);
    }
    this.stopTimer();
  }

  render() {
    return null;
  }
}

ReactIdle.defaultProps = {
  onIdleStart: () => {},
  onIdleEnd: () => {},
  onIdleTimeout: () => {},
};
