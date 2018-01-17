import React, { Component } from 'react';

export default class ErrorBoundary extends Component {
  state = {
    error: false,
    info: null,
  };

  componentDidCatch(error, info) {
    this.setState({ error, info });
  }

  componentWillReceiveProps(props) {
    if (this.props.resetOnProps) {
      this.setState({ error: false, info: null });
    }
  }

  render() {
    if (this.state.error) {
      return (
        <banner class="warning">
          <p>
            <b>An Error Occured</b>
          </p>
          <pre>{this.state.error.toString()}</pre>
          <pre>{this.state.info.componentStack.replace(/^\n/gm, '')}</pre>
        </banner>
      );
    }
    return this.props.children;
  }
}
