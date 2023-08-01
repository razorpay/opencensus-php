const baseMeta = {
  key: 'event',
  label: 'Events and Tickets',
  card: {
    title: 'Events and Tickets',
    description: 'Take your event live by adding venue details, event date/time and event images.',
    img: '/img/payment_pages/events_and_tickets.jpg',
  },
  quillPrefill: [
    {
      insert: 'About the event ',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert: '# Add a description and highlights of the event\n\nVenue ',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert: '# Name of the venue for your event \n\nStarts at ',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert: '# Date and time of the start of the event\n\nEnds at ',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert: '# Date and time of the end of the event\n\nImage Gallery? ',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert: '# Select and upload some images for your event\n\nOrganiser information ',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert:
        '# Organisation / organiser  description with address and contact information\n\nRegistration Details?',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert: '# Information about passes / packages, website details',
    },
  ],
};

export const i18nEventsTicketsMeta = {
  ...baseMeta,
  card: {
    ...baseMeta.card,
    img: '/img/payment_pages/i18n/event-and-ticket.svg',
  },
};

export default {
  IN: baseMeta,
  MY: i18nEventsTicketsMeta,
};
