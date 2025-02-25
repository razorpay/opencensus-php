import React, { Component } from 'react';
import AsyncButton from 'react-async-button';

export default class BaseToggler extends Component {
  UNSAFE_componentWillMount() {
    this.setState({ show: this.props.show });
  }

  toggle = () => {
    this.setState({
      show: !this.state.show,
    });

    if (this.props.onToggleClick) {
      return this.props.onToggleClick();
    }
  };
}

BaseToggler.defaultProps = {
  show: false,
};
