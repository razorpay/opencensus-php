import { PropTypes, Component } from 'react';
import cx from 'classnames';
import { makeArray } from 'rzp/utils/rzp-utils';

class Alert extends Component {
  constructor() {
    super(...arguments);
    this.state = {
      close: false,
    };
    this.close = ::this.close;
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.message && nextProps.message !== this.props.message) {
      this.setState({
        close: false,
      });
      window.scrollTo(0, 0)
    }
  }

  close() {
    this.setState({
      close: true,
    });
  }

  render() {
    let props = this.props;
    let msgs = makeArray(props.message);

    if (!(!this.state.close && msgs.length)) {
      return null;
    }

    return (
      <div
        class={cx(
          'alert alert-dismissable',
          props.type === 'error' ? 'alert-danger' : 'alert-success'
        )}
        style={{ borderRadius: 0 }}
      >
        <button type="button" class="close" onClick={this.close}>
          <span>×</span>
        </button>

        <ul
          class={`${msgs.length === 1 ? 'list-unstyled' : ''}`}
          style={{ paddingLeft: '15px' }}
        >
          {msgs.map((msg, index) => <li key={index}>{JSON.stringify(msg)}</li>)}
        </ul>
      </div>
    );
  }
}

Alert.displayName = 'FormAlert';

Alert.propTypes = {
  type: PropTypes.string,
};

export default Alert;
