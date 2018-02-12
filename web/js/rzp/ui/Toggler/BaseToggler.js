import { Component } from 'react';
import AsyncButton from 'react-async-button';

export default class BaseToggler extends Component {
  componentWillMount() {
    this.setState({ show: this.props.show });
  }

  toggle = () => {
    this.setState({
      show: !this.state.show,
    });

    if (!this.state.show && this.props.onToggleClick) {
      return this.props.onToggleClick();
    }
  };
}

BaseToggler.defaultProps = {
  show: false,
};
