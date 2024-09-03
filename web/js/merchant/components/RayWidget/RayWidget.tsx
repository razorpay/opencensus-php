import React from 'react';
import { importRemote } from 'merchant/utils/dynamic-remotes';
import lazyLoader from 'merchant/routes/LazyLoader';
import { analyticsTrack } from 'common/utils/analytics';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { connect } from 'react-redux';
import { User } from 'common/typings';

declare global {
  interface Window {
    cdnDashboardAssetsUrl: string;
  }
}

const loadModule = async ({ module, scope }) =>
  importRemote({
    url: window.cdnDashboardAssetsUrl,
    scope,
    module,
  });

const RayChat = lazyLoader(() =>
  /**  webpackChunkName: "Raychat" */ loadModule({
    module: 'raychat',
    scope: 'ray',
  }),
);

const RayWidget = ({
  user,
  org,
  mode,
}: {
  user: User;
  org: Record<string, unknown>;
  mode: 'test' | 'live';
}) => {
  return (
    <ErrorBoundary rank={Ranks.P1} team={Teams.CARE} FallbackComponent={() => <></>}>
      <SuspenseWithLoader>
        <RayChat user={user} org={org} mode={mode} track={analyticsTrack} />
      </SuspenseWithLoader>
    </ErrorBoundary>
  );
};

export default connect(({ session: { user, mode, org } }) => {
  return { user, mode, org };
}, null)(RayWidget);
