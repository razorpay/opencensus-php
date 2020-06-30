import { Label, Description } from 'common/new-ui/Input';
import { PowerSelect } from 'react-power-select';

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

        this.setState({
          selectedOption: defaultOption || null,
        });
      }
    }
  }

  checkIfControlledComponent() {
    if (
      this.props.hasOwnProperty('defaultValue') &&
      this.props.hasOwnProperty('value')
    ) {
      throw 'Both props defaultValue or value are not allowed to InputDropdown';
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

    return options.find(option => {
      if (option.hasOwnProperty(optionValuePath)) {
        return option[optionValuePath] === currentValue;
      }

      throw 'optionValuePath is not present in the options';
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
      disabled,
    } = this.props;

    const { selectedOption } = this.state;

    return (
      <div class={`Input Input--PowerSelect ${className}`}>
        <Label text={label} />

        <div class="Input-content">
          <div class="Input-elWrapper">
            <div class="Input-el">
              {name && (
                <input
                  name={name}
                  value={selectedOption[optionValuePath]}
                  hidden
                  readOnly
                />
              )}
              <PowerSelect
                class={dropdownElementClass}
                placeholder={placeholder}
                options={options}
                optionLabelPath={optionLabelPath}
                optionComponent={optionComponent}
                onChange={this.handleChange}
                selected={selectedOption}
                showClear={false}
                searchEnabled={false}
                disabled={disabled}
              />
            </div>
          </div>
          <Description text={description} />
        </div>
      </div>
    );
  }
}
