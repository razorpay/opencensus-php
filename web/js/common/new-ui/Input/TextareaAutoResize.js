import React from 'react';
import { classList } from 'common/utils/rzp-utils';
import Input from 'common/new-ui/Input';

export default class TextareaAutoResize extends React.PureComponent {
  autoAdjustHeight(target) {
    if (!target) {
      return;
    }

    const content = target.value;
    const resizerEl = this.resizerEl;

    resizerEl.value = content;
    const newHeight = resizerEl.scrollHeight;

    target.style.height = newHeight + 'px';
  }

  componentDidMount() {
    this.autoAdjustHeight(this.inputEl);
  }

  handleOnInput = e => {
    const { target } = e;
    this.autoAdjustHeight(target);

    this.props.onInput && this.props.onInput(e);
  };

  get extraChildren() {
    return (
      <React.Fragment key="textareaAutoResize">
        <textarea
          className="Input-el Input-el--resizer"
          ref={this.setResizerElRef}
          readOnly
        />
        {this.props.children}
      </React.Fragment>
    );
  }

  setResizerElRef = el => (this.resizerEl = el);
  setElRef = el => (this.inputEl = el);

  render() {
    const { className, children, ...restProps } = this.props;

    return (
      <Input.Textarea
        {...restProps}
        className={classList('Input--TextareaAutoResize', this.props.className)}
        setRef={this.setElRef}
        onInput={this.handleOnInput}
        onKeyPress={e => {
          if (e.which === 13) {
            e.preventDefault();
            return;
          }
        }}
        extraChildren={this.extraChildren}
      />
    );
  }
}
