import { Component, Children } from 'react';
import PropTypes from 'prop-types';
import ConfirmModal from './ConfirmModal';

export default class ConfirmModalProvider extends Component {
  getChildContext() {
    return {
      confirm: this.confirm,
    };
  }

  constructor(props, context) {
    super(props, context);
    this.state = {
      confirmResolve: null,
      confirmReject: null,
      show: false,
      options: {},
    };
    this.confirm = this.confirm.bind(this);
    this.affirm = this.affirm.bind(this);
    this.abort = this.abort.bind(this);
  }

  componentDidMount() {
    this.state.options.onMount && this.state.options.onMount();
  }

  componentWillUnmount() {
    this.state.options.onUnmount && this.state.options.onUnmount();
  }

  confirm(options = {}) {
    const promise = new Promise((resolve, reject) => {
      this.setState({
        confirmResolve: resolve,
        confirmReject: reject,
      });
    });

    if (typeof options === 'string') {
      options = {
        message: options,
      };
    }

    if (options.forceClose) {
      return this.setState({
        show: false,
      });
    }

    options.message =
      typeof options.message === 'undefined' ? 'Are you sure to continue ?' : options.message;
    options.affirmativeLabel = options.affirmativeLabel || 'OK';
    options.abortLabel = options.abortLabel || 'Cancel';

    options.onMount && options.onMount();

    this.setState({
      show: true,
      options,
    });

    return promise;
  }

  affirm() {
    const action = this.state.options.action;
    const returnFn = action && action();
    if (returnFn && typeof returnFn.then === 'function') {
      return returnFn
        .then(() => {
          this.close();
        })
        .catch(() => this.close());
    }

    this.close();
    return this.state.confirmResolve();
  }

  abort() {
    this.state.options.abort && this.state.options.abort();

    this.close();
    return this.state.confirmReject();
  }

  close() {
    this.setState({
      show: false,
    });
  }

  render() {
    const { show, options } = this.state;
    return (
      <div>
        <ConfirmModal show={show} options={options} onAffirm={this.affirm} onAbort={this.abort} />

        {Children.only(this.props.children)}
      </div>
    );
  }
}

ConfirmModalProvider.propTypes = {
  children: PropTypes.element.isRequired,
};

ConfirmModalProvider.childContextTypes = {
  confirm: PropTypes.func,
};
