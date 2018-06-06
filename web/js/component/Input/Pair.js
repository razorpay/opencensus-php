import debounce from 'rzp/utils/debounce';
import { Label, inputClass } from './index';

/* Pair is key-value pair*/
export default class Pair extends React.PureComponent {
  className = 'InputGroup';
  state = {
    maxAllowedPairs: this.props.maxAllowedPairs || 10,
    pairs: this.props.defaultValue || [],
  };

  onChange = debounce(this.props.onChange, 250);

  onAddNew = e => {
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
        document
          .getElementsByName(`notes[${freshPairs.length - 1}][key]`)[0]
          .focus(),
      10
    );
  };

  updateField = (e, field) => {
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

  updateKey = e => {
    this.updateField(e, 'key');
  };
  updateValue = e => {
    this.updateField(e, 'value');
  };

  removePair = e => {
    const pairId = e.currentTarget.dataset.id;

    if (pairId > -1) {
      let freshPairs = [...this.state.pairs];
      freshPairs.splice(pairId, 1);

      this.onChange(freshPairs);

      this.setState({
        pairs: freshPairs,
      });
    }
  };

  render() {
    const { label } = this.props;

    const tempStyle = !!this.state.pairs.length
      ? { borderLeft: '3px solid #f4f4f4', paddingLeft: 20 }
      : {};

    return (
      <div class={inputClass({ props: this.props })}>
        <Label text={label} />
        <div class="Input-content Input--Pair" style={tempStyle}>
          {!!this.state.pairs.length &&
            this.state.pairs.map((pair, idx) => (
              <div class="InputGroup" key={idx}>
                <div class="Input">
                  <div class="Input-elWrapper">
                    <input
                      class="Input-el Input-el--after"
                      name={`${this.props.name}[${idx}][key]`}
                      placeholder="Title (key)"
                      data-id={idx}
                      onChange={this.updateKey}
                      value={this.state.pairs[idx].key}
                    />
                    <span
                      class="Input-addons Input-addons--after Input-addons--clickable"
                      data-id={idx}
                      onClick={this.removePair}
                    >
                      <i class="i i-close text-danger" />
                    </span>
                  </div>
                </div>

                <div class="Input">
                  <div class="Input-elWrapper">
                    <textarea
                      class="Input-el"
                      name={`notes[${idx}][value]`}
                      placeholder="Description (value)"
                      data-id={idx}
                      onChange={this.updateValue}
                      value={this.state.pairs[idx].value}
                    />
                  </div>
                </div>
              </div>
            ))}

          {this.state.pairs.length < this.state.maxAllowedPairs ? (
            <button
              type="button"
              class="btn-link no-padding"
              onClick={this.onAddNew}
            >
              + Add New
            </button>
          ) : null}
        </div>
      </div>
    );
  }
}
