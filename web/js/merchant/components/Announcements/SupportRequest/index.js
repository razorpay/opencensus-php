import React from 'react';
import { getFormattedDate } from 'merchant/views/TicketSupport/utils';

export default function SupportRequest({ tickets }) {
  const firstTicket = tickets.length > 0 ? tickets[0] : undefined;
  const ticketType = firstTicket?.custom_fields?.cf_created_by || 'merchant';
  return (
    <div className="support-request-wrapper">
      <div className="support-request-alert-container">
        <div className="banner-meesage">
          <div className="banner-icon">
            <img
              class="circle-support-alert"
              src="https://cdn.razorpay.com/static/assets/ticket-system/circle-alert.svg"
              alt="banner-icon"
            />
          </div>
          <span className="banner-message-text"> Details Requested by Razorpay</span>
        </div>

        <div className="ticket-seperator" />
        <div className="ticket-wrapper">
          <div className="ticket">
            <div className="ticket-detail-holder">
              <div className="ticket-title">{firstTicket?.subject}</div>
              <div className="ticket-separator" />
              <div className="ticket-description">{firstTicket?.description_text}</div>
            </div>
            <div className="banner-reply-button">
              {firstTicket && (
                <a
                  href={`/app/ticket-support/rzpind/${firstTicket?.id}/${ticketType}/conversation`}
                  className="btn btn-primary"
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  Reply Now &gt;
                </a>
              )}
              <div className="text-danger before-due-date-text">
                Before {getFormattedDate(new Date(firstTicket.fr_due_by))}
              </div>
            </div>
          </div>
          {tickets?.length > 1 && (
            <a
              href="/app/ticket-support/tickets/agent"
              target="_blank"
              className="moreTickets"
              rel="noreferrer noopener"
            >
              {tickets?.length - 1} More Requests &nbsp; &#8594;
            </a>
          )}
        </div>
      </div>
    </div>
  );
}
