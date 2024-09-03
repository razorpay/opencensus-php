import { MockedRequest, rest } from 'msw';
import { setupServer } from 'msw/node';

import { confirmedUsersMock, createInviteMock, invitationsMock } from './mocks';
import { RoleT } from '../../types';

export const fetchInvitesHandler = () =>
  rest.get('*/merchant/api/*/invitations', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({ status_code: 200, success: true, data: invitationsMock }),
    );
  });

export const fetchMerchantUsersHandler = () =>
  rest.get('*/merchant/api/*/merchants-users', (req, res, ctx) => {
    return res(
      ctx.status(200),
      ctx.json({ status_code: 200, success: true, data: confirmedUsersMock }),
    );
  });

interface SendInviteRequestT {
  metadata: {
    name: string;
  };
  contact_mobile: string;
  role: RoleT;
}

export const sendInviteHandler = () =>
  rest.post('*/merchant/api/*/invitations', (req: MockedRequest<SendInviteRequestT>, res, ctx) => {
    const { metadata, contact_mobile, role } = req.body;
    const { name } = metadata;
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          metadata: {
            name,
          },
          contact_mobile,
          role,
          id: createInviteMock.id,
        },
      }),
    );
  });

export const updateInvitationHandler = () =>
  rest.patch(
    '*/merchant/api/*/invitations/:id',
    (req: MockedRequest<SendInviteRequestT>, res, ctx) => {
      const { metadata, role } = req.body;
      const { name } = metadata;
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: {
            metadata: {
              name,
            },
            role,
          },
        }),
      );
    },
  );

interface CancelInvitationT {
  params: {
    id: string;
  };
}

export const cancelInvitationHandler = () =>
  rest.delete<CancelInvitationT>('*/merchant/api/*/invitations/:id', (req, res, ctx) => {
    const { id } = req.params;
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          id,
        },
      }),
    );
  });

export const deleteUserHandler = () =>
  rest.put<CancelInvitationT>('*/merchant/api/*/users/:id/detach', (req, res, ctx) => {
    const { id } = req.params;
    return res(
      ctx.status(200),
      ctx.json({
        status_code: 200,
        success: true,
        data: {
          id,
        },
      }),
    );
  });

export const server = setupServer(...[]);
