import React, { Component } from 'react';

import './styles.styl';

class Overlay extends Component {
  constructor(props) {
    super(props);
  }

  componentDidMount() {
    const parentNode = this.node.parentNode;

    this.node.style.width = parentNode.clientWidth + 'px';
    this.node.style.height = parentNode.clientHeight + 'px';
  }

  render() {
    const { children } = this.props;

    return (
      <div ref={node => (this.node = node)} className="rzp-overlay">
        <div className="overlay-inner">
          <div className="overlay-content">{children}</div>
        </div>
      </div>
    );
  }
}

export default Overlay;
