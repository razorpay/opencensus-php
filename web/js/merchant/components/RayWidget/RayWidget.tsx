import React, { useEffect } from 'react';
import { importRemote } from 'merchant/utils/dynamic-remotes';
import lazyLoader from 'merchant/routes/LazyLoader';
import { analyticsTrack } from 'common/utils/analytics';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { connect } from 'react-redux';
import { User } from 'common/typings';
import { TicketSystemEmitter } from 'merchant/care/init';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import { fireCustomEvent } from 'merchant/components/Support/utils';

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
  useEffect(() => {
    // Using Existing Emitter setup desing for RayChat to maintain consistency
    // Will revamp this in future after rampup

    CreateTicketEmitter.on('toggle-help-section', () => {
      fireCustomEvent({
        event: 'toggle-help-section',
      });
    });
    CreateTicketEmitter.on('create-ticket', (id, pcb, lcb, prompt) => {
      fireCustomEvent({
        event: 'create-ticket',
        data: {
          id,
          pcb,
          lcb,
          prompt,
        },
      });
    });
    TicketSystemEmitter.on('openModal', (module, initialData, prompt) => {
      fireCustomEvent({
        event: 'open-ticket-modal',
        data: {
          module,
          initialData,
          prompt,
        },
      });
    });
    TicketSystemEmitter.on('closeModal', () => {
      fireCustomEvent({
        event: 'close-ticket-modal',
      });
    });
  }, []);

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
