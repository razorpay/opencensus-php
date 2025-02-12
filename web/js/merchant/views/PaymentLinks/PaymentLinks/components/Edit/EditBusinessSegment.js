import Input from 'common/new-ui/Input';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { getCustomNotesOptions } from 'merchant/views/PaymentLinks/PaymentLinks/Create/Fields';

export default class EditBusinessSegment extends React.Component {
  constructor(props) {
    super(props);

    let { type, options } = getCustomNotesOptions();
    this.TYPE = type;
    this.OPTIONS = options;

    this.state = this.resetState();
  }

  resetState() {
    return {
      notes: this.props.value[this.TYPE],
      isUpdating: false,
    };
  }

  /* Handle save of new note */
  saveAndUpdate = event => {
    this.setState({
      isUpdating: true,
    });

    const notes = {
      [this.TYPE]: event.target.value,
    };

    return this.props
      .editFn({
        notes,
      })
      .then(resp => {
        if (resp && resp.data) {
          this.props.trackerFn('Edit Notes (Saved)');
          // Handle failed case..
          this.setState({
            notes,
            isUpdating: false,
          });
        }

        return resp;
      });
  };

  render() {
    return (
      <EntityDetailRow label={<div className="m-t">{this.TYPE}</div>}>
        <Input.Select
          name="notes"
          onChange={this.saveAndUpdate}
          defaultValue={this.state.notes}
          disabled={this.state.isUpdating}
          options={this.OPTIONS}
        />
      </EntityDetailRow>
    );
  }
}
