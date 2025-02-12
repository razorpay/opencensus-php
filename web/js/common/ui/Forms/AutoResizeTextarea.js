import { Component } from 'react';

const getUnitlessValue = value => value.replace(/[^-\d\.]/g, '');

export default class AutoResizeTextarea extends Component {
  constructor() {
    super(...arguments);
    this.state = {
      height: 'auto',
    };

    this.measureHeight = this.measureHeight.bind(this);
  }

  componentDidMount() {
    let textarea = this.textarea;
    let computedStyle = window.getComputedStyle(textarea);
    this.lineHeight = getUnitlessValue(computedStyle.lineHeight);
    this.borderTopWidth = Number(
      getUnitlessValue(computedStyle.borderTopWidth)
    );
    this.borderBottomWidth = Number(
      getUnitlessValue(computedStyle.borderBottomWidth)
    );

    this.measureHeight();
  }

  UNSAFE_componentWillReceiveProps() {
    this.measureHeight();
  }

  measureHeight(event = {}) {
    let $target = event.target || this.textarea;
    setTimeout(() => {
      let scrollHeight =
        Number($target.scrollHeight) || Number($target.rows) * this.lineHeight;
      this.setState({
        height: `${scrollHeight +
          this.borderTopWidth +
          this.borderBottomWidth}px`,
      });
    }, 100);
  }

  render() {
    let { input, meta, onChange, style = {}, ...otherProps } = this.props;
    style.height = this.state.height;

    return (
      <textarea
        value={input.value}
        {...otherProps}
        style={style}
        ref={input => (this.textarea = input)}
        onChange={event => {
          this.measureHeight(event);
          input.onChange(event, event.target.value);
          onChange(event);
        }}
      />
    );
  }
}

AutoResizeTextarea.defaultProps = {
  onChange: () => {},
};
