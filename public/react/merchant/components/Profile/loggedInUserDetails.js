import React, { PureComponent } from 'react';
import { titleCase } from 'rzp/utils/rzp-utils';

export default class LoggedInUserDetails extends PureComponent {
  render() {
    const { loggedInUser } = this.props;
    return (
      <div class="row wrapper">
        <div class="panel panel-default">
          <div class="list-group">
            <a class="list-group-item">
              <span class="pull-right">
                {titleCase(loggedInUser.name)}
              </span>
              User Name
            </a>
            <a href={`mailto:${loggedInUser.email}`} class="list-group-item">
              <span class="pull-right">{loggedInUser.email}</span>
              Login Email
            </a>
            <a class="list-group-item">
              <span class="pull-right">{role}</span> {/*roletoname*/}
              Role
            </a>
          </div>
        </div>
      </div>
    );
  }
}
