import { NavLink } from 'react-router-dom';

export default function NewAppLink({ toNewApplication }) {
  return (
    <div className=" application-details-container col-lg-6">
      <NavLink to={toNewApplication}>
        <div className="new-application application-details">
          <div className="app-icon-container">
            <img className="app-icon" src="/img/default-app-logo.svg" alt="" />
          </div>
          <div className="app-details-container">
            <div className="app-name">
              <strong>Application Name</strong>
            </div>
            <div className="app-id">App ID: 0000000000001</div>
            <div className="app-created-on">Created on: 00, 0000</div>
          </div>
          <div className="pull-right">
            <button className="btn btn-primary">Create Application</button>
          </div>
        </div>
      </NavLink>
    </div>
  );
}
