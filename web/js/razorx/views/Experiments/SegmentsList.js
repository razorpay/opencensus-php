import debounce from 'common/utils/debounce';
import Field, { TextAreaField, SelectField } from 'razorx/components/ui/Field';
import { notifyError } from 'razorx/components/Modal';

export default class extends React.Component {
  state = { segmentsList: this.props.defaultValue || [{}] };

  addNewSegment = e => {
    const { segmentsList } = this.state;

    if (segmentsList.length >= TYPES.length * this.props.variantsList) {
      notifyError('Max Segments already added for this Experiment');
      return;
    }

    const newSegmentsList = segmentsList.concat();
    newSegmentsList.splice(segmentsList.length + 1, 0, {});
    this.setState({ segmentsList: newSegmentsList });
  };

  removeSegment = i => {
    const newSegmentsList = this.state.segmentsList.concat();
    if (newSegmentsList.length <= 1) {
      notifyError('At least 1 Segment is required');
      return;
    }

    newSegmentsList.splice(i, 1);

    this.setState({ segmentsList: newSegmentsList });

    setTimeout(() => {
      this.props.onChange && this.props.onChange(newSegmentsList);
    });
  };

  updateSegmentsList = (i, segment) => {
    const newSegmentsList = this.state.segmentsList.concat();
    newSegmentsList[i] = segment;
    this.setState({ segmentsList: newSegmentsList });

    setTimeout(() => {
      this.props.onChange && this.props.onChange(newSegmentsList);
    });
  };

  render() {
    const { variantsList } = this.props;

    return (
      <div className="Input--SegmentList">
        {this.state.segmentsList.map((s, ix) => (
          <Segment
            key={ix}
            index={ix}
            value={s}
            variantsList={variantsList}
            addNewSegment={this.addNewSegment}
            removeSegment={this.removeSegment}
            updateSegmentsList={this.updateSegmentsList}
          />
        ))}
        <button
          type="button"
          className="btn btn--pill"
          onClick={this.addNewSegment}
          style={{ marginTop: 12 }}
        >
          <i className="i i-return-key" /> Add Segment
        </button>
      </div>
    );
  }
}

const TYPES = ['ramp', 'whitelist', 'blacklist', 'contextramp'];

class Segment extends React.Component {
  state = { segment: this.props.value || {} };

  componentDidUpdate(prevProps) {
    if (
      prevProps.value !== this.props.value &&
      this.props.value !== this.state.value
    ) {
      this.setState({ segment: this.props.value });
    }
  }

  onChangeVariant = e => {
    this.setState(
      {
        segment: {
          ...this.state.segment,
          variant: e.target.value,
        },
      },
      () => {
        this.updateSegment();
      }
    );
  };

  onChangeType = e => {
    const type = e.target.value;
    const newSegment = { ...this.state.segment };

    if (['ramp'].indexOf(type) > -1) {
      delete newSegment.ids;
    } else if (['whitelist', 'blacklist'].indexOf(type) > -1) {
      delete newSegment.weight;
    }

    newSegment.type = type;

    this.setState(
      {
        segment: newSegment,
      },
      () => {
        this.updateSegment();
      }
    );
  };

  onAddIds = e => {
    this.setState(
      {
        segment: {
          ...this.state.segment,
          ids: e.target.value,
        },
      },
      () => {
        this.updateSegment();
      }
    );
  };

  onAddWeight = e => {
    this.setState(
      {
        segment: {
          ...this.state.segment,
          weight: Number(e.target.value),
        },
      },
      () => {
        this.updateSegment();
      }
    );
  };

  handleKeyPress = e => {
    // Hit enter
    if (e.which == 13) {
      this.props.addNewSegment(e);
      e.preventDefault();
    }
  };

  get hasWeight() {
    const type = this.state.segment.type;
    return ['ramp', 'contextramp'].indexOf(type) > -1;
  }

  get hasIds() {
    const type = this.state.segment.type;
    return ['whitelist', 'blacklist', 'contextramp'].indexOf(type) > -1;
  }

  updateSegment() {
    this.props.updateSegmentsList(this.props.index, this.state.segment);
  }

  updateSegment = debounce(this.updateSegment.bind(this), 50);

  removeSegment = _ => {
    this.props.removeSegment(this.props.index);
  };

  render() {
    const segment = this.state.segment;

    return (
      <div className="Input-Segment">
        <span className="Input-el-btn" onClick={this.removeSegment}>
          &times;
        </span>

        <SelectField
          label={'Segment ' + (this.props.index + 1)}
          placeholder="Feature Variant"
          onChange={this.onChangeVariant}
          value={segment.variant}
        >
          <option value="">--Select Variant--</option>
          {this.props.variantsList.map((v, ix) => (
            <option key={ix} value={v}>
              {v}
            </option>
          ))}
        </SelectField>

        <SelectField
          placeholder="Type"
          onChange={this.onChangeType}
          value={segment.type}
        >
          <option value="">--Select Type--</option>
          {TYPES.map((t, ix) => (
            <option key={ix} value={t}>
              {t}
            </option>
          ))}
        </SelectField>
        {this.hasWeight && (
          <Field
            onChange={this.onAddWeight}
            placeholder="weight (1 => 0.001%)"
            onKeyPress={!this.hasIds ? this.handleKeyPress : undefined}
            value={segment.weight || ''}
            autoFocus
          />
        )}
        {this.hasIds && (
          <TextAreaField
            onChange={this.onAddIds}
            placeholder="Merchant IDs (Comma separated)"
            autoFocus={!this.hasWeight}
            onKeyPress={this.handleKeyPress}
            value={segment.ids || ''}
          />
        )}
      </div>
    );
  }
}
