export default {
  label: 'Donation',
  card: {
    title: 'Accepting Donations',
    description:
      'Raising money for a good cause? Our online Donations template saves you valuable time so you can focus on your cause.',
    img: '/img/payment_pages/donation.jpg',
  },
  quillPrefill: [
    {
      insert: 'Donation Cause',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert:
        '# Provide information about your fundraising drive\n\nCampaign starts on ',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert: '# Date and time of the start of the drive\n\nCampaign ends on ',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert: '# Date and time of the end of the drive\n\nOrganiser details ',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert:
        '# Organisation / Organiser description with address and contact information\n\nTax exemption details ',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert:
        '# Details of tax exemption eligibility for donors\n\nCampaign images',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert: '# Select and upload up to 4 images for fundraising drive\n',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
  ],
};
