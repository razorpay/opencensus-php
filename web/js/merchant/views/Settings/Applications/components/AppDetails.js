import Time from 'common/ui/Time';
import { NavLink } from 'react-router-dom';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';

export function AppDetailsLoader() {
  return (
    <div className="application-details-container col-lg-6" data-testId="skeleton-loader">
      <div className="application-details ">
        <div className="app-icon-container">
          <PlaceholderLoader style={{ height: '100%', width: '100%', display: 'block' }} />
        </div>

        <div className="app-details-container">
          <PlaceholderLoader />
          <PlaceholderLoader style={{ display: 'block' }} />
          <PlaceholderLoader />
        </div>
      </div>
    </div>
  );
}
export default function AppDetails(props) {
  const data = props.data;
  const isConnected = props.type === 'connected';
  const Comp = isConnected ? 'div' : NavLink;

  const onClick = (e) => {
    e.preventDefault();
    props.onBtnClick(data);
  };

  return (
    <div className="application-details-container col-lg-6">
      <Comp className="application-details-inner" to={props.entityDetailLink}>
        <div className="btn-container pull-right">
          <button onClick={onClick} className="btn btn-default">
            {isConnected ? 'Revoke Access' : 'Delete Application'}
          </button>
        </div>
        <div className={`application-details ${isConnected ? 'connected-app' : ''}`}>
          <div className="app-icon-container">
            <img
              className="app-icon"
              src={
                isConnected
                  ? props.isRevokeApplicationEnabled
                    ? data.logo_url
                    : data.application.logo_url
                  : data.logo_url || '/img/default-app-logo.svg'
              }
              alt=""
            />
          </div>
          <div className="app-details-container">
            <div className="app-name">
              <strong>
                {isConnected
                  ? props.isRevokeApplicationEnabled
                    ? data.application_name
                    : data.application?.name
                  : data.name}
              </strong>
            </div>
            {!isConnected && <div className="app-id">App ID: {data.id}</div>}
            <div className="app-created-on">
              {isConnected ? 'Approved' : 'Created'} on:{' '}
              <Time
                value={props.isRevokeApplicationEnabled ? data.access_granted_at : data.created_at}
                format="DD MMM YYYY"
              />
            </div>
          </div>
        </div>
      </Comp>
    </div>
  );
}
