import React, { Component } from 'react';

class Overlay extends Component {
  constructor(props) {
    super(props);
  }

  layout() {
    const parentNode = this.node.parentNode;

    this.node.style.width = parentNode.clientWidth + 'px';
    this.node.style.height = parentNode.clientHeight + 'px';
  }

  componentDidMount() {
    this.layout();
  }

  UNSAFE_componentWillReceiveProps() {
    this.layout();
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
