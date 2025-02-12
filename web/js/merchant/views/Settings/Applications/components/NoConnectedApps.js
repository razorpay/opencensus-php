import PlaceholderLoader from 'common/ui/PlaceholderLoader';

export function NoConnectedApps() {
  return (
    <div className="text-center content-body connected-apps">
      <div className="img">
        <img src="/img/Illustration-noconnectedapp.svg" alt="" />
      </div>
      <div className="panel-body text-muted">No connected apps</div>
    </div>
  );
}

export function LoadingConnectedApps() {
  return (
    <div className="text-center content-body connected-apps">
      <div className="img">
        <img src="/img/Illustration-noconnectedapp.svg" alt="" />
        <PlaceholderLoader />
      </div>
      <div className="panel-body text-muted">Fetching connected apps...</div>
    </div>
  );
}
