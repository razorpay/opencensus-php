import { NavLink } from 'react-router-dom';

export default function NewAppLink({ toNewApplication }) {
  return (
    <div class=" application-details-container col-lg-6">
      <NavLink to={toNewApplication}>
        <div class="new-application application-details">
          <div class="app-icon-container">
            <img class="app-icon" src="/img/default-app-logo.svg" alt="" />
          </div>
          <div class="app-details-container">
            <div class="app-name">
              <strong>Application Name</strong>
            </div>
            <div class="app-id">App ID: 0000000000001</div>
            <div class="app-created-on">Created on: 00, 0000</div>
          </div>
          <div class="pull-right">
            <button class="btn btn-primary">Create Application</button>
          </div>
        </div>
      </NavLink>
    </div>
  );
}
