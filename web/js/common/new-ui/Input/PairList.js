import React from 'react';
// eslint-disable-next-line import/no-cycle
import { Label, inputClass } from './index';
import debounce from 'common/utils/debounce';
import { classList } from 'common/utils/rzp-utils';
import Button from 'common/new-ui/Button';

/* Pair is key-value pair*/
export default class PairList extends React.PureComponent {
  className = 'Input--pair';

  state = {
    maxAllowedPairs: this.props.maxAllowedPairs || 10,
    pairs: this.props.defaultValue || [],
  };

  onChange = debounce(this.props.onChange, 250); // Optimization to avoid parent re-render on every key press

  onAddNew = () => {
    // eslint-disable-next-line react/no-access-state-in-setstate
    const freshPairs = [...this.state.pairs];
    freshPairs.push({
      key: '',
      value: '',
    });

    this.setState({
      pairs: freshPairs,
    });

    setTimeout(
      () =>
        document.getElementsByName(`${this.props.name}[${freshPairs.length - 1}][key]`)[0].focus(),
      10,
    );
    if (this.props.onAddNew) {
      this.props.onAddNew(freshPairs);
    }
  };

  updateField = (e, field) => {
    e.persist();
    const pairId = e.currentTarget.dataset.id;

    if (pairId > -1) {
      // eslint-disable-next-line react/no-access-state-in-setstate
      const freshPairs = [...this.state.pairs];
      freshPairs[pairId][field] = e.target.value;

      this.onChange(freshPairs, field);
      this.setState({
        pairs: freshPairs,
      });
    }
  };

  updateKey = (e) => {
    this.updateField(e, 'key');
  };
  updateValue = (e) => {
    this.updateField(e, 'value');
  };

  removePair = (e) => {
    const pairId = e.currentTarget.dataset.id;

    if (pairId > -1) {
      // eslint-disable-next-line react/no-access-state-in-setstate
      const freshPairs = [...this.state.pairs];
      freshPairs.splice(pairId, 1);

      this.onChange(freshPairs);

      this.setState({
        pairs: freshPairs,
      });
    }
  };

  render() {
    return (
      <div className={classList(inputClass(this), !!this.state.pairs.length && 'isExpanded')}>
        <Label text={this.props.label} className={this.props.labelClass} />
        <div className="Input-content">
          {!!this.state.pairs.length &&
            this.state.pairs.map((pair, idx) => (
              <Pair
                key={idx}
                name={this.props.name}
                idx={idx}
                removePair={this.removePair}
                pair={this.state.pairs[idx]}
                updateKey={this.updateKey}
                updateValue={this.updateValue}
                onBlurTitle={this.props.onBlurTitle}
                onBlurDesc={this.props.onBlurDesc}
              />
            ))}

          {this.state.pairs.length < this.state.maxAllowedPairs ? (
            <Button.Transparent type="button" className="Btn--Link" onClick={this.onAddNew}>
              + Add New
            </Button.Transparent>
          ) : null}
        </div>
      </div>
    );
  }
}

class Pair extends React.Component {
  state = {};

  onFocusTitle = () => {
    this.setState({
      focusTitle: true,
    });
  };

  onBlurTitle = (e) => {
    const pairId = e.currentTarget.dataset.id;

    this.setState({
      focusTitle: false,
    });

    if (this.props.onBlurTitle) {
      this.props.onBlurTitle(e, pairId);
    }
  };

  onFocusDesc = () => {
    this.setState({
      focusDesc: true,
    });
  };

  onBlurDesc = (e) => {
    const pairId = e.currentTarget.dataset.id;

    this.setState({
      focusDesc: false,
    });

    if (this.props.onBlurDesc) {
      this.props.onBlurDesc(e, pairId);
    }
  };

  render() {
    const { name, idx, pair, updateKey, updateValue } = this.props;

    return (
      <div
        className={classList(
          'Input-pair',
          (this.state.focusDesc || this.state.focusTitle) && 'is-focused',
        )}
      >
        <div className="Input-elWrapper">
          <input
            className="Input-el Input-el--after"
            name={`${name}[${idx}][key]`}
            placeholder="Title (key)"
            data-id={idx}
            onChange={updateKey}
            value={pair.key}
            onBlur={this.onBlurTitle}
            onFocus={this.onFocusTitle}
          />
          <span
            className="Input-addons Input-addons--after Input-addons--clickable"
            data-id={idx}
            onClick={this.props.removePair}
          >
            <i className="i i-close" />
          </span>
        </div>

        <div className="Input-pair-separator" />
        <div className="Input-elWrapper">
          <textarea
            className="Input-el"
            name={`${name}[${idx}][value]`}
            placeholder="Description (value)"
            data-id={idx}
            onChange={updateValue}
            value={pair.value}
            onBlur={this.onBlurDesc}
            onFocus={this.onFocusDesc}
          />
        </div>
      </div>
    );
  }
}

/* Helper method to return array as per internal notes structure */
export function onChangeNotes(pairs) {
  const notes = {};

  pairs.forEach((p) => {
    if (p.key || p.value) {
      notes[p.key] = p.value;
    }
  });

  return notes;
}
