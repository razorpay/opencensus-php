import { Component } from 'react';

export default class TourStep extends Component {
  render() {
    return (
      <div class={`TourStep ${this.props.className}`}>
        {this.props.children}
      </div>
    );
  }
}

TourStep.defaultProps = {
  attachment: 'middle left',
  targetAttachment: 'middle right',
  offset: '0 -15px',
  arrowTopPos: '50%',
  arrowLeftPos: '50%',
};
