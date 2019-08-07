import { Component } from 'react';
import AsyncButton from 'react-async-button';

export default class MerchantUserActions extends Component {
  removeUser = () => {
    return this.props.removeUser(this.props.user.id).then(response => {
      if (response.success) {
        this.props.onRemove();
      }
    });
  };

  render() {
    return (
      <>
        <AsyncButton
          class="btn btn-default m-l"
          text="Remove"
          data-tip="Removes user from your team"
          onClick={this.removeUser}
        />
      </>
    );
  }
}
