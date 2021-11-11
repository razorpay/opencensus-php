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

  onAddNew = (e) => {
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
    this.props.onAddNew && this.props.onAddNew(freshPairs);
  };

  updateField = (e, field) => {
    e.persist();
    const pairId = e.currentTarget.dataset.id;

    if (pairId > -1) {
      const freshPairs = [...this.state.pairs];
      freshPairs[pairId][field] = e.target.value;

      this.onChange(freshPairs);
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
      <div class={classList(inputClass(this), !!this.state.pairs.length && 'isExpanded')}>
        <Label text={this.props.label} />
        <div class="Input-content">
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
              />
            ))}

          {this.state.pairs.length < this.state.maxAllowedPairs ? (
            <Button.Transparent type="button" class="Btn--Link" onClick={this.onAddNew}>
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

  onFocusTitle = (e) => {
    this.setState({
      focusTitle: true,
    });
  };

  onBlurTitle = (e) => {
    this.setState({
      focusTitle: false,
    });

    this.props.onBlurTitle && this.props.onBlurTitle(e);
  };

  onFocusDesc = (e) => {
    this.setState({
      focusDesc: true,
    });
  };

  onBlurDesc = (e) => {
    this.setState({
      focusDesc: false,
    });

    this.props.onBlurDesc && this.props.onBlurDesc(e);
  };

  render() {
    const { name, idx, pair, updateKey, updateValue } = this.props;

    return (
      <div
        class={classList(
          'Input-pair',
          (this.state.focusDesc || this.state.focusTitle) && 'is-focused',
        )}
      >
        <div class="Input-elWrapper">
          <input
            class="Input-el Input-el--after"
            name={`${name}[${idx}][key]`}
            placeholder="Title (key)"
            data-id={idx}
            onChange={updateKey}
            value={pair.key}
            onBlur={this.onBlurTitle}
            onFocus={this.onFocusTitle}
          />
          <span
            class="Input-addons Input-addons--after Input-addons--clickable"
            data-id={idx}
            onClick={this.props.removePair}
          >
            <i class="i i-close" />
          </span>
        </div>

        <div class="Input-pair-separator" />
        <div class="Input-elWrapper">
          <textarea
            class="Input-el"
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
