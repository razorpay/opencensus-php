import { Component, Fragment } from 'react';

export default class SettlementsExample extends Component {
  state = {
    durations: [],
  };
  componentDidMount() {}

  getRemainingValues = () => {
    let jsx = [];
    let width = 100 / (this.props.duration - 1);
    if (this.props.holiday) {
      width = 100 / this.props.duration;
    }
    for (var i = 1; i <= this.props.duration - 1; i++) {
      jsx.push(
        <div class="pull-right s-header" key={i} style={{ width: width + '%' }}>
          <div class="s-working" />
          T+{i}
        </div>,
      );
    }
    if (this.props.holiday) {
      jsx.push(
        <div
          class="pull-right s-header s-border holiday-div"
          key={i}
          style={{ width: width + '%' }}
        >
          <div class="s-holiday" />
          Holiday
        </div>,
      );
    }
    return jsx;
  };

  render() {
    const width = 100 / (this.props.duration - 1);
    return (
      <Fragment>
        <div style={{ marginTop: '20px', display: 'flex' }}>
          <div class="pull-left s-header" style={{ width: width + '%' }}>
            <div class="s-start" />
            T
            <br />
            <span style={{ fontSize: '13px' }}>Transaction Capture Date</span>
            <hr />
          </div>
          {this.getRemainingValues()}
          <div class="pull-right s-header" style={{ width: width + '%' }}>
            <div class="s-end" />
            T+{this.props.duration}
            <br />
            <span style={{ fontSize: '13px' }}>Settlement Date</span>
          </div>
        </div>
      </Fragment>
    );
  }
}
