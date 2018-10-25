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
      const curSelectedIndex =
        dataSet.optionIndex < this.props.options.length
          ? dataSet.optionIndex
          : 0;
      const selectedValue = this.props.options[curSelectedIndex].label;

      this.setState({ optionIndex: curSelectedIndex });

      const mainFormElement = document.getElementsByName(this.props.name)[0];
      setNativeValue(mainFormElement, selectedValue);
      mainFormElement.dispatchEvent(new Event('change', { bubbles: true }));

      this.props.onChange &&
        this.props.onChange({ index: curSelectedIndex, value: selectedValue });

      this.toggleExpansion();
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

    return (
      <div class={inputClass(this)}>
        <Label text={this.props.label} />
        <div class="Input-content">
          <div class="Input-elWrapper Select-elWrapper">
            <div onClick={this.toggleExpansion}>
              <input
                class="Input-el"
                name={this.props.name}
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
