import { Component } from 'react';

export default class LogoutDialog extends Component {
  constructor(props) {
    super(props);
  }

  componentDidMount() {
    window.setTimeout(() => {
      window.location.reload();
    }, 2000);
  }

  render() {
    return (
      <div class="logout-dialog">
        <center>
          <div class="modal-header">
            <h3 class="modal-title">Your session has expired!</h3>
          </div>
          <div class="modal-body">
            <p>You will be redirected to Login</p>
          </div>
        </center>
      </div>
    );
  }
}
