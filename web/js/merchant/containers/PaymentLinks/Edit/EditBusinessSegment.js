import Input from 'component/Input';
import { getOptions } from 'merchant/containers/PaymentLinks/Links/Create/Fields';

export default class EditBusinessSegment extends React.Component {
  state = this.resetState();

  resetState() {
    return {
      notes: this.props.value.business_segment,
      isUpdating: false,
    };
  }

  /* Handle save of new note */
  saveAndUpdate = event => {
    this.setState({
      isUpdating: true,
    });

    const notes = {
      business_segment: event.target.value,
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
      <React.Fragment>
        <Input.Select
          name="notes"
          onChange={this.saveAndUpdate}
          defaultValue={this.state.notes}
          disabled={this.state.isUpdating}
          options={getOptions(this.props.id)}
        />
      </React.Fragment>
    );
  }
}
