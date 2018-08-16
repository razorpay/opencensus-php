import { onChangeNotes } from 'component/Input/PairList';
import Input from 'component/Input';
import { AsyncBtn } from 'component/Button';

export default class EditNotes extends React.Component {
  state = this.resetState();

  resetState() {
    const notes = Object.keys(this.props.value).map(k => ({
      key: k,
      value: this.props.value[k],
    }));

    return {
      notes,
    };
  }

  /* Handle save of new note */
  saveAndUpdate = pairs => {
    const notes = { ...pairs };

    return this.props
      .editFn({
        notes: onChangeNotes(pairs),
      })
      .then(resp => {
        if (resp && resp.data) {
          // Handle failed case..
          this.setState({
            notes,
          });
        }

        return resp;
      });
  };

  render() {
    return (
      <React.Fragment>
        <Input.EditablePairsList
          name="notes"
          saveAndUpdate={this.saveAndUpdate}
          defaultValue={this.state.notes}
          trackerFn={this.props.trackerFn.bind(null, this.props.entityId)}
        />
      </React.Fragment>
    );
  }
}
