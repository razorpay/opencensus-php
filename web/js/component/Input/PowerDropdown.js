import { Label, Error, inputClass } from './index';

/*
* Reference: https://github.com/facebook/react/issues/10135#issuecomment-314441175
*
* This is helper fn. as a work around for dispatching manual events on native elements.
*
* */
function setNativeValue(element, value) {
  const valueSetter = Object.getOwnPropertyDescriptor(element, 'value').set;
  const prototype = Object.getPrototypeOf(element);
  const prototypeValueSetter = Object.getOwnPropertyDescriptor(
    prototype,
    'value'
  ).set;

  if (valueSetter && valueSetter !== prototypeValueSetter) {
    prototypeValueSetter.call(element, value);
  } else {
    valueSetter.call(element, value);
  }
}

export default class extends React.Component {
  className = 'Input--PowerDropdown';

  constructor(props) {
    super(props);

    let defaultIndex = 0;
    const hasDefaultValue = typeof this.props.defaultValue !== 'undefined';

    if (hasDefaultValue) {
      if (typeof props.options[0] === 'object') {
        props.options.forEach((o, i) => {
          if (o.value === props.defaultValue) {
            defaultIndex = i;
            return false;
          }
        });
      } else {
        defaultIndex = props.options.indexOf(props.defaultValue);
      }
    }

    this.state = {
      mature: this.props.mature,
      selectedOptionIndex: defaultIndex > 0 ? defaultIndex : 0,
    };
  }

  focus = e => {
    this.setState({ focus: true });
  };

  blur = e => {
    this.setState({ focus: false });
  };

  onSelection = e => {
    const dataSet = e.target.dataset;
    const { options, onChange } = this.props;

    if (dataSet && dataSet.optionIndex !== void 0) {
      const selectedOptionIndex =
        dataSet.optionIndex < options.length ? dataSet.optionIndex : 0; // Safe check

      const selectedValue =
        typeof options[0] === 'object'
          ? options[selectedOptionIndex].value
          : options[selectedOptionIndex];

      this.setState({ selectedOptionIndex: selectedOptionIndex });

      const mainFormElement = document.getElementsByName(this.props.name)[0];
      setNativeValue(mainFormElement, selectedValue);
      mainFormElement.dispatchEvent(new Event('change', { bubbles: true }));

      onChange &&
        onChange({ index: selectedOptionIndex, value: selectedValue });

      this.toggleExpansion();
    }

    e.stopPropagation();
  };

  toggleExpansion = e => {
    this.setState({ isDropdownExpanded: !this.state.isDropdownExpanded });
  };

  render() {
    const {
      name,
      label,
      defaultValue,
      options,
      customOptionComponent: OptionComponent,
      customSelectedOptionComponent: SelectedComponent,
    } = this.props;

    const { selectedOptionIndex, isDropdownExpanded } = this.state;

    const selectedOptionLabel =
      typeof options[0] === 'object'
        ? options[selectedOptionIndex].label
        : options[selectedOptionIndex];

    return (
      <div class={inputClass(this)}>
        {/* Contains actual value of dropdown. Automatically considered in Form using via serializer */}
        <input name={name} defaultValue={defaultValue} hidden />
        <Label text={label} />
        <div class="Input-content">
          <div class="Input-elWrapper Select-elWrapper">
            <div
              onClick={this.toggleExpansion}
              onKeyPress={e => {
                if (e.which === 13) {
                  this.toggleExpansion(e);
                }
              }}
              tabIndex="0"
              onFocus={this.focus}
              onBlur={this.blur}
            >
              <input
                class="Input-el"
                readOnly
                value={selectedOptionLabel}
                hidden={!!SelectedComponent}
              />
              {SelectedComponent && (
                <div class="Input-el Input-el--customSelection">
                  <SelectedComponent option={options[selectedOptionIndex]} />
                </div>
              )}
            </div>

            {isDropdownExpanded && (
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
    if (this.dropdownlist.contains(e.target)) {
      e.stopPropagation();
      return;
    }

    this.props.toggleExpansion();
  }

  handleEscapePress = ::this.handleEscapePress;
  handleDocumentClick = ::this.handleDocumentClick;

  render() {
    const { options, onSelection, OptionComponent } = this.props;

    return (
      <div
        class="Input-list"
        ref={dropdownlist => {
          this.dropdownlist = dropdownlist;
        }}
      >
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
