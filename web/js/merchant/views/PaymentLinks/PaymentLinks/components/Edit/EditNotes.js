import React from "react";
import { onChangeNotes } from 'common/new-ui/Input/PairList';
import Input from 'common/new-ui/Input';
import { AsyncBtn } from 'common/new-ui/Button';

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
          this.props.trackerFn('Edit Notes (Saved)');
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
          trackerFn={this.props.trackerFn}
          isRoleAllowedEdit={this.props.isRoleAllowedEdit}
        />
      </React.Fragment>
    );
  }
}
