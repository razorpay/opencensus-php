import React, { Component } from 'react';

import './styles.styl';

class Sticky extends Component {
  constructor(props) {
    super(props);

    this.state = {
      isSticky: false,
    };

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

    this.node.style.width = styles.width;
    this.node.style.height = styles.height;

    Object.keys(styles).forEach(styleName => {
      this.contentElement.style[styleName] = styles[styleName];
    });

    this.setState({ isSticky: true });
  }

  unStick() {
    this.node.removeAttribute('style');
    this.contentElement.removeAttribute('style');

    this.setState({
      isSticky: false,
    });
  }

  handleScroll() {
    return window.scrollY >= this.props.stickWhen
      ? !this.state.isSticky && this.stick()
      : this.state.isSticky && this.unStick();
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
    const classNames = ['rzp-sticky'],
      styles = {};

    if (this.state.isSticky) {
      classNames.push('sticky');
      styles.width = this.node;
    }

    return (
      <div className={classNames.join(' ')} ref={node => (this.node = node)}>
        <div
          ref={node => (this.contentElement = node)}
          className="sticky-content"
        >
          {this.props.children}
        </div>
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
