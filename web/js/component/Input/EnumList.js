import Button from 'component/Button';
import { classList } from 'common/util';

export default class EnumAdder extends React.PureComponent {
  state = {
    options: this.props.defaultValue || [''],
  };

  addNewOption = e => {
    const lastFocused = this.state.lastFocusedIndex;

    const newOptions = this.state.options.concat();
    newOptions.splice(lastFocused + 1, 0, '');
    this.setState({ options: newOptions, lastFocusedIndex: lastFocused + 1 });
  };

  removeOption = i => {
    const newOptions = this.state.options.concat();
    newOptions.splice(i, 1);
    this.setState({ options: newOptions });
  };

  updateOption = (i, val) => {
    const newOptions = this.state.options.concat();
    newOptions[i] = val;
    this.setState({ options: newOptions });
  };

  updateLastFocused = i => {
    this.setState({ lastFocusedIndex: i });
  };

  render() {
    const { options } = this.state;

    return (
      <div class={classList('Input Input--enum-list', this.props.className)}>
        <div class="Input-content">
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
              />
            );
          })}
        </div>
        <Button.Transparent
          class="btn-link"
          onClick={this.addNewOption}
          type="button"
        >
          <i class="i i-return-key" /> Add Another Option
        </Button.Transparent>
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

  handleChange = e => {
    this.setState({ value: e.target.value, isChanged: true });
  };

  render() {
    const {
      index,
      addNewOption,
      removeOption,
      updateOption,
      newOptionIndex,
    } = this.props;

    return (
      <div
        class={classList(
          'Input-elWrapper Input-enum-option',
          this.state.focus && 'is-focused'
        )}
      >
        <input
          ref={el => (this.el = el)}
          class="Input-el"
          value={this.state.value}
          onChange={this.handleChange}
          onKeyPress={e => {
            if (e.which == 13) {
              addNewOption(e);
            }
          }}
          onFocus={e => {
            this.focus(e);
            this.props.updateLastFocused(this.props.index);
          }}
          onMouseEnter={this.focus}
          onBlur={e => {
            this.blur();
            if (this.state.isChanged) {
              updateOption(index, this.state.value);
              this.setState({
                isChanged: false,
              });
            }
          }}
          onMouseLeave={this.blur}
          autoFocus={newOptionIndex === index}
        />

        <span class="Input-el-btn" onClick={() => removeOption(index)}>
          &times;
        </span>
      </div>
    );
  }
}
