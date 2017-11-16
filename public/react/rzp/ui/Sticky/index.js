import React, { Component } from 'react';

import './styles.styl';

class Sticky extends Component {
  constructor(props) {
    super(props);

    this.isSticky = false;
    this.handleScroll = this.handleScroll.bind(this);
  }

  stick() {
    const node = this.node,
      borderBox = node.getBoundingClientRect(),
      styles = {
        position: 'fixed',
        width: `${node.clientWidth}px`,
        height: `${node.clientHeight}px`,
        left: `${borderBox.left}px`,
        top: `${this.props.stickAt}px`,
      };

    Object.keys(styles).forEach(styleName => {
      node.style[styleName] = styles[styleName];
    });

    this.isSticky = true;
  }

  unStick() {
    this.node.removeAttribute('style');
    this.isSticky = false;
  }

  handleScroll() {
    return window.scrollY >= this.props.stickWhen
      ? !this.isSticky && this.stick()
      : this.isSticky && this.unStick();
  }

  componentDidMount() {
    const container = this.props.container;

    window.addEventListener('scroll', this.handleScroll);

    return container.scrollTop >= this.props.stickWhen && this.stick();
  }

  componentWillUnmount() {
    window.removeEventListener('scroll', this.handleScroll);
  }

  render() {
    return (
      <div className="rzp-sticky" ref={node => (this.node = node)}>
        {this.props.children}
      </div>
    );
  }
}

Sticky.defaultProps = {
  stickWhen: 0,
  stickAt: 0,
  container: document.documentElement,
};

export default Sticky;
