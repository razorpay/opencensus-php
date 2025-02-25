import React from 'react';
import { Label, Error, inputClass } from './index';
import { classList } from 'common/utils/rzp-utils';
import { setNativeValue } from 'common/utils/rzp-utils';
import { findDOMNode } from 'react-dom';

/*
 * Returns object or value inside that object at a given level
 * */
export function getValueOfKeyAtLevel(key, optionObj, stringTree) {
  if (!stringTree) {
    return optionObj[0];
  }

  return (function getVal(optionObj, indices) {
    if (indices.length === 1) {
      return key ? optionObj[indices[0]][key] : optionObj[indices[0]];
    }

    // For last index, options shouldn't exist
    return getVal(
      optionObj[indices[0]].options || optionObj[indices[0]],
      indices.splice(1)
    );
  })(optionObj, stringTree.split(' '));
}

export default class PowerDropdown extends React.Component {
  className = 'Input--PowerDropdown';

  state = {
    mature: this.props.mature,
    selectedOptionIndexTree:
      typeof this.props.defaultValue !== 'undefined'
        ? this.props.defaultValue
        : '',
  };

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (nextProps !== this.props) {
      this.el && this.valid();
    }
  }

  valid() {
    let { required, validator, requiredError, patternError } = this.props;

    let el = this.el;
    let value = el.value;
    let validity = el.validity;
    let error = '';

    if (validity.valueMissing) {
      error = requiredError || this.requiredError;
    } else if (validity.patternMismatch) {
      error = patternError || this.patternError;
    } else if (validator) {
      error = validator(value) || '';
      el.setCustomValidity(error);
    }

    this.setState({ error });
  }

  setRef = el => {
    this.el = el;
    if (el) {
      this.valid();
    }
  };

  focus = e => {
    this.setState({ focus: true });
  };

  blur = e => {
    this.setState({ focus: false });

    if (this.state.touched) {
      this.setState({ mature: true });
    }
  };

  onSelection = e => {
    const dataSet = e.target.dataset;
    const { options, onChange } = this.props;

    if (dataSet && dataSet.optionIndex !== void 0) {
      const selectedOptionIndexTree = dataSet.optionIndex;

      // Assuming all options are of same type, so checking 0th index
      const selectedOption = getValueOfKeyAtLevel(
        undefined,
        options,
        selectedOptionIndexTree
      );

      this.setState({ selectedOptionIndexTree });

      const mainFormElement = document.getElementsByName(this.props.name)[0];
      // Sets value as stringed tree if nested, otherwise value of that option
      setNativeValue(mainFormElement, selectedOption.value);
      mainFormElement.dispatchEvent(new Event('change', { bubbles: true }));

      this.valid();

      onChange &&
        onChange({ index: selectedOptionIndexTree, option: selectedOption });

      this.toggleExpansion();
      if (!this.state.mature || !this.state.touched) {
        this.setState({ touched: true });
      }
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
      'label',
      options,
      selectedOptionIndexTree
    );

    return (
      <div
        className={inputClass(this)}
        ref={el => {
          this.powerDropdown = el;
        }}
      >
        {/* Contains actual value of dropdown. Automatically considered in Form using via serializer */}
        <input
          name={name}
          defaultValue={defaultValue}
          hidden
          ref={this.setRef}
        />
        <Label text={label} />
        <div className="Input-content">
          <div className="Input-elWrapper Select-elWrapper">
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
                className="Input-el"
                readOnly
                value={selectedOptionLabel}
                hidden={!!SelectedComponent}
              />
              {SelectedComponent && (
                <div className="Input-el Input-el--customSelection">
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
            <Error text={this.state.error || this.props.propagatedError} />

            {isDropdownExpanded && (
              <DropDownList
                options={this.props.options}
                selectedOptionIndexTree={selectedOptionIndexTree}
                onSelection={this.onSelection}
                OptionComponent={OptionComponent}
                toggleExpansion={this.toggleExpansion}
                level={0}
                parentRef={this.powerDropdown}
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

  handleDocumentClick = e => {
    if (
      !this.props.parentRef ||
      findDOMNode(this.props.parentRef).contains(e.target)
    ) {
      e.stopPropagation();
      return;
    }

    this.props.toggleExpansion();
  };

  handleEscapePress = this.handleEscapePress.bind(this);
  handleDocumentClick = this.handleDocumentClick.bind(this);

  render() {
    const {
      options,
      onSelection,
      toggleExpansion,
      OptionComponent,
      selectedOptionIndexTree,
      level,
    } = this.props;

    const selectedIndexAtLevel = selectedOptionIndexTree
      ? selectedOptionIndexTree.split(' ')[0]
      : selectedOptionIndexTree;
    let valueAtSelectedIndex;

    if (typeof selectedIndexAtLevel !== 'undefined') {
      valueAtSelectedIndex = selectedIndexAtLevel
        ? options[selectedIndexAtLevel].value
        : options[0].value;
    }
    return (
      <div className="Input-list">
        {options.map((o, i) => {
          const displayLabel = typeof o === 'object' ? o.label : o;
          const hasSubOptions = !!o.options;

          return (
            <div
              className={classList(
                'Input-list-item',
                valueAtSelectedIndex === o.value && 'selected'
              )}
              key={i}
              onClick={hasSubOptions ? undefined : onSelection}
              data-option-index={level == 0 ? i : level + ' ' + i}
            >
              {OptionComponent ? (
                <React.Fragment key={i}>
                  <OptionComponent option={o} />
                  {!!o.options && <i className="i i-chevron-right" />}
                </React.Fragment>
              ) : (
                <React.Fragment key={i}>
                  <span className="display-label">{displayLabel}</span>
                  {hasSubOptions && <i className="i i-chevron-right" />}
                </React.Fragment>
              )}
              {hasSubOptions && (
                <DropDownList
                  options={o.options}
                  selectedOptionIndexTree={(() => {
                    let s = selectedOptionIndexTree.split(' ');

                    if (s.length > Number(level) + 1) {
                      s = s.splice(1).join(' ');
                    } else {
                      s = undefined;
                    }

                    return s;
                  })()}
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
