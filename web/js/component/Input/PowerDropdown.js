import { Label, Error, inputClass } from './index';

export default class extends React.Component {
  className = 'Input--PowerDropdown';
  state = {
    mature: this.props.mature,
    optionIndex:
      typeof this.props.defaultValue !== 'undefined'
        ? this.props.defaultValue
        : 0,
  };

  focus = e => {
    this.setState({ focus: true });
  };

  blur = e => {
    this.setState({ focus: false });
  };

  onSelection = e => {
    const dataSet = e.target.dataset;

    if (dataSet && dataSet.optionIndex !== void 0) {
      this.setState({ optionIndex: dataSet.optionIndex });

      this.props.onChange && this.props.onChange(dataSet.optionIndex);
    }

    e.stopPropagation();
  };

  toggleExpansion = e => {
    this.setState({ expandDropdown: !this.state.expandDropdown });
  };

  render() {
    const OptionComponent = this.props.customOptionComponent;
    const SelectedComponent = this.props.customSelectedOptionComponent;

    const curSelectedIndex =
      this.state.optionIndex < this.props.options.length
        ? this.state.optionIndex
        : 0;
    const selectedValue = this.props.options[curSelectedIndex].label;

    return (
      <div class={inputClass(this)}>
        <Label text={this.props.label} />
        <div class="Input-content">
          <div class="Input-elWrapper Select-elWrapper">
            <div onClick={this.toggleExpansion}>
              <input
                class="Input-el"
                name={this.props.name}
                value={selectedValue}
                readOnly
                hidden={!!SelectedComponent}
              />
              {SelectedComponent && (
                <div class="Input-el Input-el--customSelection">
                  <SelectedComponent
                    option={this.props.options[curSelectedIndex]}
                  />
                </div>
              )}
            </div>

            {this.state.expandDropdown && (
              <DropDownList
                options={this.props.options}
                onSelection={this.onSelection}
                OptionComponent={OptionComponent}
                toggleExpansion={this.toggleExpansion}
              />
            )}
          </div>
          <Error text={this.props.propagatedError} />
        </div>
      </div>
    );
  }
}

class DropDownList extends React.PureComponent {
  componentDidMount() {
    document.addEventListener('keydown', this.handleEscapePress);
    document.addEventListener('click', this.handleDocumentClick);
  }

  componentWillUnmount() {
    document.removeEventListener('keydown', this.handleEscapePress);
    document.removeEventListener('click', this.handleDocumentClick);
  }

  handleEscapePress(e) {
    if (event.which === 27) {
      this.props.toggleExpansion();
    }
  }

  handleDocumentClick(e) {
    this.props.toggleExpansion();
  }

  handleEscapePress = ::this.handleEscapePress;
  handleDocumentClick = ::this.handleDocumentClick;

  render() {
    const { options, onSelection, OptionComponent } = this.props;

    return (
      <div class="Input-list">
        {options.map((o, i) => {
          let optionVal, displayLabel;
          if (typeof o === 'object') {
            optionVal = typeof o.value === 'undefined' ? i : o.value;
            displayLabel = o.label;
          } else {
            optionVal = i === 0 ? '' : i;
            displayLabel = o;
          }

          return (
            <div
              class="Input-list-item"
              ref={dropdownlist => {
                this.dropdownlist = dropdownlist;
              }}
              key={i}
              onClick={onSelection}
              data-option-index={i}
            >
              {OptionComponent ? (
                <OptionComponent key={i} option={o} />
              ) : (
                <React.Fragment key={i}>{displayLabel}</React.Fragment>
              )}
            </div>
          );
        })}
      </div>
    );
  }
}
