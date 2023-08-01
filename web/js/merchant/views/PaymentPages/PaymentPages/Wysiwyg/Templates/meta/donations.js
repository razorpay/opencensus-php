const baseMeta = {
  key: 'donation',
  label: 'Donation',
  card: {
    title: 'Accepting Donations',
    description:
      'Start collecting online donations by adding cause information, images and campaign duration.',
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
      insert: '# Provide information about your fundraising drive\n\nCampaign starts on ',
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
      insert: '# Details of tax exemption eligibility for donors\n\nCampaign images',
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
  ],
};

export default {
  IN: baseMeta,
  MY: baseMeta,
};
