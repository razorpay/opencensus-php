import debounce from 'rzp/utils/debounce';
import Field, { TextAreaField, SelectField } from 'ui/Field';

export default class extends React.Component {
  state = { segmentsList: [{}] };

  addNewSegment = e => {
    const { segmentsList } = this.state;

    const newSegmentsList = segmentsList.concat();
    newSegmentsList.splice(segmentsList.length + 1, 0, {});
    this.setState({ segmentsList: newSegmentsList });
  };

  removeOption = i => {
    const newSegmentsList = this.state.segmentsList.concat();
    newSegmentsList.splice(i, 1);

    this.setState({ segmentsList: newSegmentsList });
  };

  updateSegment = (i, val) => {
    const newSegmentsList = this.state.segmentsList.concat();
    newSegmentsList[i] = val;
    this.setState({ segmentsList: newSegmentsList });

    setTimeout(() => {
      this.props.onChange && this.props.onChange(this.state.segmentsList);
    });
  };

  render() {
    const { variantsList = ['on', 'off', 'wow'] } = this.props;

    return (
      <div class="Input--SegmentList">
        {this.state.segmentsList.map((s, ix) => (
          <div key={ix}>
            <Segment
              index={ix}
              value={s}
              variantsList={variantsList}
              addNewSegment={this.addNewSegment}
              removeSegment={this.removeSegment}
              updateSegment={this.updateSegment}
            />
          </div>
        ))}
        <button
          type="button"
          class="btn btn--pill"
          onClick={this.addNewOption}
          style={{ marginTop: 12 }}
        >
          <i class="i i-return-key" /> Add Segment
        </button>
      </div>
    );
  }
}

const TYPES = ['ramp', 'whitelist', 'blacklist', 'context-ramp'];

class Segment extends React.Component {
  state = { value: this.props.value || {} };

  updateSegment = debounce(::this.props.updateSegment, 50);

  onChangeVariant = e => {
    this.setState(
      {
        value: {
          ...this.state.value,
          variant: e.target.value,
        },
      },
      () => {
        this.updateSegment(this.props.index, { value: this.state.value });
      }
    );
  };

  onChangeType = e => {
    this.setState(
      {
        value: {
          ...this.state.value,
          type: e.target.value,
        },
      },
      () => {
        this.updateSegment(this.props.index, { value: this.state.value });
      }
    );
  };

  render() {
    const { type } = this.state.value;

    return (
      <div class="Input-Segment">
        <SelectField
          placeholder="Feature Variant"
          onChange={this.onChangeVariant}
        >
          <option value="">--Select Variant--</option>
          {this.props.variantsList.map((v, ix) => (
            <option key={ix} value={v}>
              {v}
            </option>
          ))}
        </SelectField>

        <SelectField placeholder="Type" onChange={this.onChangeType}>
          <option value="">--Select Type--</option>
          {TYPES.map((t, ix) => (
            <option key={ix} value={t}>
              {t}
            </option>
          ))}
        </SelectField>
        {['ramp', 'context-ramp'].indexOf(type) > -1 && (
          <Field placeholder="weight (1 => 0.001%)" />
        )}
        {['whitelist', 'blacklist', 'context-ramp'].indexOf(type) > -1 && (
          <TextAreaField placeholder="Merchant IDs (Comma separated)" />
        )}
      </div>
    );
  }
}
