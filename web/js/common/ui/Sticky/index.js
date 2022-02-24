import React, { Component } from 'react';

import debounce from 'common/utils/debounce';

class Sticky extends Component {
  constructor(props) {
    super(props);

    this.state = {
      isSticky: false,
    };

    this.handleScroll = debounce(this.handleScroll.bind(this), 50);
  }

  layout(props = this.props) {
    const node = this.node,
      borderBox = node.getBoundingClientRect(),
      styles = {
        width: `${node.clientWidth}px`,
        left: `${borderBox.left}px`,
        top: `${props.stickAt}px`,
      };

    this.node.style.width = styles.width;
    this.node.style.height = this.contentElement.clientHeight + 'px';

    Object.keys(styles).forEach(styleName => {
      this.contentElement.style[styleName] = styles[styleName];
    });
  }

  stick(props = this.props) {
    this.layout(props);
    this.setState({ isSticky: true });
  }

  unStick() {
    //can be called after unmount due to debounce
    if (this.unMounted) {
      return;
    }

    this.node.removeAttribute('style');
    this.contentElement.removeAttribute('style');

    this.setState({
      isSticky: false,
    });
  }

  toggleSticky(top) {
    return top >= this.props.stickWhen
      ? !this.state.isSticky && this.stick(this.props)
      : this.state.isSticky && this.unStick();
  }

  handleScroll() {
    window.setTimeout(() => {
      return this.toggleSticky(window.scrollY);
    });
  }

  componentDidMount() {
    const container = this.props.container;

    window.addEventListener('scroll', this.handleScroll, { passive: true });
    return this.toggleSticky(container.scrollTop);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    const { stickWhen, stickAt } = this.props;

    if (stickWhen !== nextProps.stickWhen || stickAt !== nextProps.stickAt) {
      this.layout(nextProps);

      return this.props.container.scrollTop > nextProps.stickWhen
        ? this.stick(nextProps)
        : this.unStick();
    }
  }

  componentWillUnmount() {
    this.unMounted = true;

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
