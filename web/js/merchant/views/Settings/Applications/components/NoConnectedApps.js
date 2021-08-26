import PlaceholderLoader from 'common/ui/PlaceholderLoader';

export function NoConnectedApps() {
  return (
    <div class="text-center content-body connected-apps">
      <div class="img">
        <img src="/img/Illustration-noconnectedapp.svg" alt="" />
      </div>
      <div class="panel-body text-muted">No connected apps</div>
    </div>
  );
}

export function LoadingConnectedApps() {
  return (
    <div class="text-center content-body connected-apps">
      <div class="img">
        <img src="/img/Illustration-noconnectedapp.svg" alt="" />
        <PlaceholderLoader />
      </div>
      <div class="panel-body text-muted">Fetching connected apps...</div>
    </div>
  );
}
