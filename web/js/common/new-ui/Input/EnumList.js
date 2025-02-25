import React from 'react';
import Button from 'common/new-ui/Button';
import { classList } from 'common/utils/rzp-utils';
import debounce from 'common/utils/debounce';

export default class EnumList extends React.PureComponent {
  state = {
    options: this.props.defaultValue || [''],
  };

  addNewOption = e => {
    let { lastFocusedIndex: lastFocused, options } = this.state;

    if (lastFocused == null) {
      // Checking for null or undefined
      lastFocused = options.length - 1; // Does it from last onwards by default
    }

    if (options.length && !options[lastFocused]) {
      return;
    }

    const newOptions = options.concat();
    newOptions.splice(lastFocused + 1, 0, '');
    this.setState({ options: newOptions, lastFocusedIndex: lastFocused + 1 });
  };

  removeOption = i => {
    const newOptions = this.state.options.concat();
    newOptions.splice(i, 1);
    this.setState({ options: newOptions }, () => {
      this.updateEnumList && this.updateEnumList(this.state.options);
    });
  };

  updateEnumList = this.props.onChange && debounce(this.props.onChange.bind(this), 60);

  updateOption = (i, val) => {
    const newOptions = this.state.options.concat();
    newOptions[i] = val;
    this.setState({ options: newOptions }, () => {
      this.updateEnumList && this.updateEnumList(this.state.options);
    });
  };

  updateLastFocused = i => {
    this.setState({ lastFocusedIndex: i });
  };

  render() {
    const { options } = this.state;

    return (
      <div className={classList('Input Input--enum-list', this.props.className)}>
        <div className="Input-content">
          {options.map((o, i) => {
            return (
              <EnumOption
                key={i}
                index={i}
                value={o}
                addNewOption={this.addNewOption}
                removeOption={this.removeOption}
                updateOption={this.updateOption}
                updateLastFocused={this.updateLastFocused}
                newOptionIndex={this.state.lastFocusedIndex}
                inputClass={this.props.inputClass}
              />
            );
          })}
        </div>
        {this.props.addNewBtn ? (
          <span
            onClick={this.addNewOption}
            style={{ display: 'inline-block', marginTop: 12 }}
          >
            {this.props.addNewBtn()}
          </span>
        ) : (
          <Button.Transparent
            className="btn-link"
            onClick={this.addNewOption}
            type="button"
          >
            {!!options.length && <i className="i i-return-key" />}
            {!!options.length ? 'Add Another Option' : 'Add an Option'}
          </Button.Transparent>
        )}
      </div>
    );
  }
}

class EnumOption extends React.PureComponent {
  state = { value: this.props.value || '' };

  focus = e => {
    this.setState({ focus: true });
  };

  blur = e => {
    this.setState({ focus: false });
  };

  componentDidUpdate(prevProps) {
    if (
      prevProps.value !== this.props.value &&
      this.props.value !== this.state.value
    ) {
      this.setState({ value: this.props.value });
    }

    if (
      prevProps.newOptionIndex !== this.props.newOptionIndex &&
      this.props.index === this.props.newOptionIndex
    ) {
      this.el.focus();
    }
  }

  // updateOption = debounce(::this.props.updateOption, 50);
  updateOption = debounce(this.props.updateOption.bind(this), 50);

  handleChange = e => {
    e.stopPropagation();
    e.preventDefault();

    this.setState({ value: e.target.value });

    setTimeout(() => {
      this.updateOption(this.props.index, this.state.value);
    });
  };

  render() {
    const {
      index,
      addNewOption,
      removeOption,
      newOptionIndex,
      inputClass,
    } = this.props;

    return (
      <div
        className={classList(
          'Input-elWrapper Input-enum-option',
          this.state.focus && 'is-focused'
        )}
      >
        <input
          ref={el => (this.el = el)}
          className={classList('Input-el', inputClass)}
          value={this.state.value}
          onChange={this.handleChange}
          onKeyPress={e => {
            // Hit enter
            if (e.which == 13) {
              addNewOption(e);
              e.preventDefault();
            }
          }}
          onFocus={e => {
            this.focus(e);
            this.props.updateLastFocused(this.props.index);
          }}
          onMouseEnter={this.focus}
          onBlur={this.blur}
          onMouseLeave={this.blur}
          autoFocus={newOptionIndex === index}
        />

        <span className="Input-el-btn" onClick={() => removeOption(index)}>
          &times;
        </span>
      </div>
    );
  }
}
