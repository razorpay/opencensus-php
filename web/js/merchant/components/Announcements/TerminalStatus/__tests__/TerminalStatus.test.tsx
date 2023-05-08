import React from 'react';
import TerminalStatus from 'merchant/components/Announcements/TerminalStatus';
import {
  TERMINAL_PROCUREMENT_STATUSES,
  STATUSES_FOR_DISPLAYING_BANNER,
  BANNER_HEADING,
} from 'merchant/components/Announcements/TerminalStatus/constant';
import { render, server, waitFor, screen, userEvent } from 'test-utils';
import { rest } from 'msw';

const updateServerResponse = (status) => {
  server.use(
    rest.get('*/merchants/config/store', (req, res, ctx) => {
      return res(
        ctx.status(200),
        ctx.json({
          status_code: 200,
          success: true,
          data: {
            upi_terminal_procurement_status_banner: status,
          },
        }),
        ctx.delay(50),
      );
    }),
  );
};

describe('TerminalStatus', () => {
  test.each(STATUSES_FOR_DISPLAYING_BANNER)(
    `Render banner when status one of ${STATUSES_FOR_DISPLAYING_BANNER.join(',')}`,
    async (status) => {
      updateServerResponse(status);

      render(<TerminalStatus user={{ activation_status: 'activated' }} isMobile />, {});

      await waitFor(() =>
        expect(screen.getByText(BANNER_HEADING[status].mobile.title)).toBeInTheDocument(),
      );
    },
  );

  test(`should not render Banner when status is ${TERMINAL_PROCUREMENT_STATUSES.NO_BANNER}`, async () => {
    updateServerResponse(TERMINAL_PROCUREMENT_STATUSES.NO_BANNER);
    render(<TerminalStatus user={{ activation_status: 'activated' }} isMobile />, {});

    await waitFor(() =>
      expect(
        screen.queryByText(BANNER_HEADING[TERMINAL_PROCUREMENT_STATUSES.PENDING].mobile.title),
      ).not.toBeInTheDocument(),
    );
  });

  test.each(STATUSES_FOR_DISPLAYING_BANNER)(
    `should remove banner when Ok got it clicked`,
    async (status) => {
      updateServerResponse(status);

      render(<TerminalStatus user={{ activation_status: 'activated' }} isMobile />, {});

      await waitFor(() =>
        expect(screen.getByText(BANNER_HEADING[status].mobile.title)).toBeInTheDocument(),
      );

      await waitFor(() => {
        const cta = screen.getByText('Okay, got it');
        expect(cta).toBeInTheDocument();
        userEvent.click(cta);
      });

      await waitFor(() =>
        expect(screen.queryByText(BANNER_HEADING[status].mobile.title)).not.toBeInTheDocument(),
      );
    },
  );
});
