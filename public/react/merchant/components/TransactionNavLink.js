import { Component, PropTypes } from 'react';

export default class TransactionNavLink extends Component {
  handleClick = e => {
    // On Click, if metaKey is false(no key pressed alongside), it doesn't perform default action
    if (!e.metaKey) {
      window.location = this.props.to;
      e.preventDefault();
    }
  };

  render() {
    return (
      <a
        href={`${this.props.to}?type=${this.props.queryParam}`}
        target="_blank"
        onClick={this.handleClick}
      >
        {this.props.children}
      </a>
    );
  }
}

TransactionNavLink.propTypes = {
  queryParam: PropTypes.string.isRequired,
};
