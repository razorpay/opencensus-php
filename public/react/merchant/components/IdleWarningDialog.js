import { Component } from 'react';
import Countdown from 'rzp/ui/Countdown';
import ProgressBar from 'rzp/ui/ProgressBar';

export default class IdleWarning extends Component {
  state = {};

  componentWillMount() {
    this.setState(
      {
        counter: this.props.countdown,
      },
      () => {
        let counter = this.state.counter;
        this.timer = setInterval(() => {
          if (counter > 0) {
            this.setState({
              counter: counter - 1,
            });
            counter--;
          }
        }, 1000);
      }
    );
  }

  componentWillUnmount() {
    clearInterval(this.timer);
  }

  render() {
    let { countdown } = this.props;
    return (
      <div>
        <div class="modal-header">
          <h3 class="modal-title">
            You'll be logged out in
            {' '}
            <span class="label label-danger">{this.state.counter}</span>
            {' '}
            seconds.
            {' '}
          </h3>
        </div>
        <div class="modal-body">
          <p>
            Your session has been idle for very long, perform some action to avoid auto logout.
          </p>
          <ProgressBar
            type="danger"
            max={countdown}
            value={this.state.counter}
          />
        </div>
      </div>
    );
  }
}
