import React, { useEffect } from 'react';
import { importRemote } from 'merchant/utils/dynamic-remotes';
import lazyLoader from 'merchant/routes/LazyLoader';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { connect } from 'react-redux';
import { User } from 'common/typings';
import { TicketSystemEmitter } from 'merchant/care/init';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import { fireCustomEvent } from 'merchant/components/Support/utils';
import { getRayUser } from './utils';

const loadModule = async ({ module, scope }) =>
  importRemote({
    scope,
    module,
  });

const RayChat = lazyLoader(() =>
  /**  webpackChunkName: "Raychat" */ loadModule({
    module: 'raychat',
    scope: 'ray',
  }),
);

const track = (event) => analyticsTrackWithUserInfo({ ...event, addUserProperties: true });

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
    window.rzpTicketSystem = {
      openModal: (module, initialData, prompt): void => {
        fireCustomEvent({
          event: 'open-ticket-modal',
          data: {
            module,
            initialData,
            prompt,
          },
        });
      },
      closeModal: (): void => {
        fireCustomEvent({
          event: 'close-ticket-modal',
        });
      },
    };
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

  const userObj = getRayUser(user);

  return (
    <ErrorBoundary rank={Ranks.P1} team={Teams.CARE} FallbackComponent={() => <></>}>
      <SuspenseWithLoader>
        <RayChat
          clientName="merchant_dashboard"
          user={userObj}
          org={org}
          mode={mode}
          track={track}
        />
      </SuspenseWithLoader>
    </ErrorBoundary>
  );
};

export default connect(({ session: { user, mode, org } }) => {
  return { user, mode, org };
}, null)(RayWidget);
