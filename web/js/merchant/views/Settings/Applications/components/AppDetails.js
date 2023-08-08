import Time from 'common/ui/Time';
import { NavLink } from 'react-router-dom';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';

export function AppDetailsLoader() {
  return (
    <div class="application-details-container col-lg-6" data-testId="skeleton-loader">
      <div class="application-details ">
        <div class="app-icon-container">
          <PlaceholderLoader style={{ height: '100%', width: '100%', display: 'block' }} />
        </div>

        <div class="app-details-container">
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
    <div class="application-details-container col-lg-6">
      <Comp class="application-details-inner" to={props.entityDetailLink}>
        <div class="btn-container pull-right">
          <button onClick={onClick} class="btn btn-default">
            {isConnected ? 'Revoke Access' : 'Delete Application'}
          </button>
        </div>
        <div class={`application-details ${isConnected ? 'connected-app' : ''}`}>
          <div class="app-icon-container">
            <img
              class="app-icon"
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
          <div class="app-details-container">
            <div class="app-name">
              <strong>
                {isConnected
                  ? props.isRevokeApplicationEnabled
                    ? data.application_name
                    : data.application?.name
                  : data.name}
              </strong>
            </div>
            {!isConnected && <div class="app-id">App ID: {data.id}</div>}
            <div class="app-created-on">
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
