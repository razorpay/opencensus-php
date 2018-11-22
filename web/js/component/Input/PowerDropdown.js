import { Label, Error, inputClass } from './index';
import { classList } from 'common/util';
import { findDOMNode } from 'react-dom';

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

/*
 * Returns object or value inside that object at a given level
 * */
export function getValueOfKeyAtLevel(key, optionObj, indicesString) {
  return (function getVal(optionObj, indices) {
    if (indices.length === 0) {
      return key ? optionObj[key] : optionObj;
    }

    // For last index, options shouldn't exist
    return getVal(
      optionObj[indices[0]].options || optionObj[indices[0]],
      indices.splice(1)
    );
  })(optionObj, indicesString.split(''));
}

export default class PowerDropdown extends React.Component {
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
      selectedOptionIndexTree: String(defaultIndex) || '0',
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
      const selectedOptionIndexTree = dataSet.optionIndex;

      const indexTree = String(selectedOptionIndexTree).split('');

      // Assuming all options are of same type, so checking 0th index
      const selectedValue = getValueOfKeyAtLevel(
        undefined,
        this.props.options,
        selectedOptionIndexTree
      );

      this.setState({ selectedOptionIndexTree });

      const mainFormElement = document.getElementsByName(this.props.name)[0];
      setNativeValue(mainFormElement, selectedOptionIndexTree);
      mainFormElement.dispatchEvent(new Event('change', { bubbles: true }));

      onChange &&
        onChange({ index: selectedOptionIndexTree, value: selectedValue });

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

    const { selectedOptionIndexTree, isDropdownExpanded } = this.state;

    // Assuming all options are of same type, so checking just 0th index
    const selectedOptionLabel = getValueOfKeyAtLevel(
      typeof options[0] === 'object' ? 'label' : undefined,
      options,
      selectedOptionIndexTree
    );

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
                  <SelectedComponent
                    option={getValueOfKeyAtLevel(
                      undefined,
                      options,
                      selectedOptionIndexTree
                    )}
                  />
                </div>
              )}
            </div>

            {isDropdownExpanded && (
              <DropDownList
                options={this.props.options}
                selectedOptionIndexTree={selectedOptionIndexTree}
                onSelection={this.onSelection}
                OptionComponent={OptionComponent}
                toggleExpansion={this.toggleExpansion}
                level={0}
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
    if (findDOMNode(this.dropdownlist).contains(e.target)) {
      e.stopPropagation();
      return;
    }

    this.props.toggleExpansion();
  }

  handleEscapePress = ::this.handleEscapePress;
  handleDocumentClick = ::this.handleDocumentClick;

  render() {
    const {
      options,
      onSelection,
      toggleExpansion,
      OptionComponent,
      selectedOptionIndexTree,
      level,
    } = this.props;

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

          const hasSubOptions = !!o.options;

          return (
            <div
              class={classList(
                'Input-list-item',
                selectedOptionIndexTree[level] == i && 'selected'
              )}
              key={i}
              onClick={hasSubOptions ? this.ignoreClick : onSelection}
              data-option-index={level == 0 ? i : level + '' + i}
            >
              {OptionComponent ? (
                <React.Fragment key={i}>
                  <OptionComponent option={o} />
                  {!!o.options && <i class="i i-chevron-right" />}
                </React.Fragment>
              ) : (
                <React.Fragment key={i}>
                  <span class="display-label">{displayLabel}</span>
                  {hasSubOptions && <i class="i i-chevron-right" />}
                </React.Fragment>
              )}
              {hasSubOptions && (
                <DropDownList
                  options={o.options}
                  selectedOptionIndexTree={selectedOptionIndexTree
                    .split('')
                    .splice(1)}
                  onSelection={onSelection}
                  OptionComponent={OptionComponent}
                  toggleExpansion={toggleExpansion}
                  level={Number(level) + 1}
                />
              )}
            </div>
          );
        })}
      </div>
    );
  }
}
