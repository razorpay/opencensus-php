const content = {
  monetizationCharges: {
    bannerId: 'enable-monetization-charges',
    noCodeApps: ['paymentLinks', 'razorpayMeLink', 'paymentPages', 'storefrontPages', 'invoices'],
    noCodeAppsBenefits: [
      'Accept payments instantly without any developer',
      'Create website-like experience for your customers',
      'Free hosting and SMS included',
      'Unlimited access to Razorpay APIs & custom integration',
      'Offer your customers a wide range of payment options, including UPI, credit cards, net banking, wallets, and EMIs',
    ],
    paymentLinks: {
      title: 'Payment Links',
      color: '#0F78AD',
      bg: '#1291D017',
      bannerText: 'A reminder on how you’ll be charged for Payment Link transactions',
      benefits: [
        'Create links to sell anywhere',
        'Send links via WhatsApp Business account',
        'We handle SMS and email notifications',
        'Integrate with ERP systems and other systems for automatic creation',
        'Send auto-reminders for unpaid invoices to customers',
      ],
    },
    razorpayMeLink: {
      title: 'Razorpay.me Link',
      color: '#C65C10',
      bg: '#E9690C17',
      bannerText: 'A reminder on how you’ll be charged for Razorpay.me transactions',
      benefits: [
        'Have your own business name in Razorpay links that builds trust and brand',
        'Use one link to collect payments from multiple customers',
        'Let customers enter and pay any amount',
        'Share link on social media',
      ],
    },
    invoices: {
      title: 'Invoices',
      color: '#305EFF',
      bg: '#305EFF17',
      bannerText: 'A reminder on how you’ll be charged for Invoices transactions',
      benefits: [
        'Get paid faster with online invoices, no need to purchase additional invoice software',
        'Get paid faster online',
        'Track and send reminders for unpaid invoices',
        'Allow customers to save/download invoice PDFs',
        'Generate invoices in bulk with file upload',
      ],
    },
    paymentPages: {
      title: 'Payment Pages',
      color: '#008743',
      bg: '#00A25117',
      benefits: [
        'Customise payment pages to match your Brand',
        'Go online with zero coding',
        'Create custom forms for customer inputs',
        'Send automated reciepts after purchase',
        'Use your own domain (add-on)',
      ],
    },
    storefrontPages: {
      title: 'Storefront Pages',
      color: '#D92D20',
      bg: '#D92D2017',
      benefits: [
        'Showcase products and accept orders',
        'Manage inventory and stock for multiple categories and products',
        'Add 100+ products and add detailed product descriptions',
        'Include multiple product images',
        {
          boldAndLightStrikeThrough: true,
          text: 'Connect with you own domain at',
          bold: 'Rs 500',
          lightStrike: 'Rs 1000',
        },
      ],
    },
    paymentAndStorefrontPages: {
      title: 'Payment and Storefront Pages',
      bannerText: 'A reminder on how you’ll be charged for Payment Page & Storefront transactions',
    },
  },
};

export default content;
