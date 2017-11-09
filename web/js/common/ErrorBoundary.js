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
    this.setState({ error: false, info: null });
  }

  render() {
    if (this.state.error) {
      return (
        <div class="limited box">
          <alert-warn>
            <p>
              <b>An Error Occured</b>
            </p>
            <pre>{this.state.error.toString()}</pre>
            <pre>{this.state.info.componentStack.replace(/^\n/gm, '')}</pre>
          </alert-warn>
        </div>
      );
    }
    return this.props.children;
  }
}
