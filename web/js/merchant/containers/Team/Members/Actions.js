import { Component } from 'react';
import AsyncButton from 'react-async-button';

export default class MembersActions extends Component {
  update = () => {
    // code to update member from team
  };

  remove = () => {
    // code to remove member from team
  };

  render() {
    return (
      <>
        <button class="btn btn-primary m-r" onClick={this.update}>
          Update
        </button>

        <AsyncButton
          class="btn btn-default"
          text="Remove"
          pendingText="Removing..."
          onClick={this.remove}
        />
      </>
    );
  }
}
