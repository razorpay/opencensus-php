import Time from 'rzp/ui/Time';
import { NavLink } from 'react-router-dom';

export default function AppDetails(props) {
  const data = props.data;
  const isConnected = props.type === 'connected';
  const Comp = isConnected ? 'div' : NavLink;
  return (
    <div class={`application-details-container col-lg-6`}>
      <Comp class="application-details-inner" to={`/applications/${data.id}`}>
        <div class="btn-container pull-right">
          <button
            onClick={e => {
              e.preventDefault();
              props.onBtnClick(data);
            }}
            class="btn btn-default"
          >
            <span>
              {isConnected ? 'Revoke Access' : 'Delete Application'}
            </span>
          </button>
        </div>
        <div
          class={`application-details ${isConnected ? 'connected-app' : ''}`}
        >
          <div class="app-icon-container">
            <img
              class="app-icon"
              src={
                isConnected
                  ? data.application.logo_url
                  : data.logo_url || 'img/default-app-logo.svg'
              }
              alt=""
            />
          </div>
          <div class="app-details-container">
            <div class="app-name">
              <strong>
                {isConnected ? data.application.name : data.name}
              </strong>
            </div>
            {!isConnected &&
              <div class="app-id">
                App ID: {data.id}
              </div>}
            <div class="app-created-on">
              {isConnected ? 'Approved' : 'Created'} on:{' '}
              <Time value={data.created_at} format="DD MMM YYYY" />
            </div>
          </div>
        </div>
      </Comp>
    </div>
  );
}
