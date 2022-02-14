import React from 'react';
import { Label, Description } from 'common/new-ui/Input';
import { PowerSelect } from 'react-power-select';
import ErrorBoundary, { Ranks } from 'common/new-ui/ErrorBoundary';

export default class InputDropdown extends React.Component {
  constructor(props) {
    super(props);

    const defaultOption = this.findSelectedOption();

    this.state = {
      selectedOption: defaultOption || null,
    };
  }

  componentDidUpdate(prevProps) {
    if (this.checkIfControlledComponent()) {
      if (prevProps.value != this.props.value) {
        const defaultOption = this.findSelectedOption();

        // eslint-disable-next-line react/no-did-update-set-state
        this.setState({
          selectedOption: defaultOption || null,
        });
      }
    }
  }

  componentDidMount() {
    if (this.props.autoFocus) {
      setTimeout(this.openDropdown, 100);
    }
  }

  openDropdown = () => {
    this.dropdown.select &&
      this.dropdown.select.setState({
        isOpen: true,
      });
  };

  checkIfControlledComponent() {
    if (this.props.hasOwnProperty('defaultValue') && this.props.hasOwnProperty('value')) {
      throw new Error('Both props defaultValue or value are not allowed to InputDropdown');
    }

    if (this.props.hasOwnProperty('value')) {
      return true;
    }

    return false;
  }

  findSelectedOption() {
    const { optionValuePath, options } = this.props;

    let currentValue;
    if (this.checkIfControlledComponent()) {
      currentValue = this.props.value;
    } else {
      currentValue = this.props.defaultValue;
    }

    return options.find((option) => {
      if (option.hasOwnProperty(optionValuePath)) {
        return option[optionValuePath] === currentValue;
      }

      throw new Error('optionValuePath is not present in the options');
    });
  }

  handleChange = ({ option }) => {
    // if value prop is passed, it implies it's a controlled component
    if (!this.checkIfControlledComponent()) {
      this.setState({
        selectedOption: option,
      });
    }

    this.props.onChange && this.props.onChange(option);
  };

  ref = (e) => (this.dropdown = e);

  render() {
    const {
      label,
      description,
      name,
      className,
      dropdownElementClass,
      placeholder,
      options,
      optionLabelPath,
      optionValuePath,
      optionComponent,
      selectedOptionComponent,
      searchEnabled = false,
      disabled,
      afterOptionsComponent,
    } = this.props;

    const { selectedOption } = this.state;

    return (
      <div class={`Input Input--PowerSelect ${className}`}>
        <Label text={label} />

        <div class="Input-content">
          <div class="Input-elWrapper">
            <div class="Input-el">
              {name && (
                <input name={name} value={selectedOption[optionValuePath]} hidden readOnly />
              )}
              <ErrorBoundary resetOnProps rank={Ranks.P2}>
                <PowerSelect
                  ref={this.ref}
                  class={dropdownElementClass}
                  placeholder={placeholder}
                  options={options}
                  optionLabelPath={optionLabelPath}
                  optionComponent={optionComponent}
                  selectedOptionComponent={selectedOptionComponent}
                  onChange={this.handleChange}
                  selected={selectedOption}
                  showClear={false}
                  searchEnabled={searchEnabled}
                  disabled={disabled}
                  afterOptionsComponent={afterOptionsComponent}
                />
              </ErrorBoundary>
            </div>
          </div>
          <Description text={description} />
        </div>
      </div>
    );
  }
}
