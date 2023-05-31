const templateId = 'TTTTTTTTTTTTTT';

const defaultModalData = {
  footer_data: {
    background_color: '#FF4A06',
    footer_text_color: '#FF4A06',
    label: 'Start saving today!',
    style: 'normal',
  },
  image: {
    alt_text: 'Offer image',
    mobile_url:
      'https://rzp-1415-prod-growth-service.s3.ap-south-1.amazonaws.com/growth/Li1SURti13VKds/Monthly Plans.png',
    url: 'https://rzp-1415-prod-growth-service.s3.ap-south-1.amazonaws.com/growth/Li1SURti13VKds/Monthly Plans.png',
  },
  label: 'default',
  offer_cta: {
    cta_background_color: '#528ff0',
    cta_font_color: '#FFFFFF',
    handler: [
      {
        type: 'url',
        url: 'https://dashboard.razorpay.com/signin?',
      },
    ],
    label: 'Know More',
    style: 'normal',
  },
  variant: 'default',
};

const thankYouModalData = {
  footer_data: {
    footer_text_color: '#FF4A06',
    header: 'Request received',
    label: 'We’ll be in touch with you about the next steps soon.',
    style: 'normal',
  },
  image: {
    alt_text: 'Offer image',
    mobile_url: 'https://cdn.razorpay.com/growth/LY2FKrsE92ede9/2 (1).png',
    url: 'https://cdn.razorpay.com/growth/LY2FKrsE92ede9/1.65% Pricing + CC (2).png',
  },
  label: 'default',
  variant: 'thank-you',
};

export { templateId, defaultModalData, thankYouModalData };
