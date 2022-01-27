const imagePath = `${window.cdnBaseUrl}/static/assets`;

const headerImageArray = [
  {
    imagePath: `${imagePath}/razorpayx/razorpayx-banking.svg`,
    imageAlt: 'Razorpay X Banking Logo',
  },
  {
    imagePath: `${imagePath}/text-separator.svg`,
    imageAlt: '',
    imageStyle: {
      marginRight: '10px',
    },
  },
  {
    imagePath: `${imagePath}/modal-asset/icici-dashboard-logo.png`,
    imageAlt: 'ICICI Business Banking',
    imageStyle: {
      width: '115px',
    },
  },
  {
    imagePath: `${imagePath}/text-separator.svg`,
    imageAlt: '',
    imageStyle: {
      marginLeft: '10px',
    },
  },
];

const houseIcon = {
  src: `${imagePath}/neostone-exclusive-offer/toolTipHouse.svg`,
  alt: 'In house',
};

const iciciBankLogo = {
  src: `${imagePath}/modal-asset/icici-dashboard-logo.png`,
  alt: 'ICICI Bank',
};

const headerDivider = {
  src: `${imagePath}/modal-asset/icici-divider.svg`,
  alt: '',
};

const iciciCAFeaturesList = [
  {
    image: {
      src: `${imagePath}/modal-asset/save-money.svg`,
      alt: 'Smart dashboard',
    },
    description: (
      <>
        Get a <span className="highlight">smart dashboard</span> and{' '}
        <span className="highlight">custom access</span> logins for your team
      </>
    ),
  },
  {
    image: {
      src: `${imagePath}/modal-asset/save-gift.svg`,
      alt: 'Add beneficiaries',
    },
    description: (
      <>
        Add beneficiaries instantly, <span className="highlight">no cooling off period</span>
      </>
    ),
  },
  {
    image: {
      src: `${imagePath}/modal-asset/bank-on-the-go.svg`,
      alt: 'Bank on the go',
    },
    description: (
      <>
        <span className="highlight">Bank on the go</span> with India’s best Mobile Banking App
      </>
    ),
  },
  {
    image: {
      src: `${imagePath}/modal-asset/choose-this.svg`,
      alt: 'Automate taxes',
    },
    description: (
      <>
        Automate <span className="highlight">taxes, vendors payments, and payroll</span>
      </>
    ),
  },
  {
    image: {
      src: `${imagePath}/modal-asset/tally.svg`,
      alt: 'Link your accounts',
    },
    description: (
      <>
        Link your account to <span className="highlight">ZohoBooks, Tally,</span> and{' '}
        <span className="highlight">Quickbooks!</span>
      </>
    ),
  },
];

const OFFER_DETAILS = {
  ICICI: {
    header: {
      text: 'Give your existing ICICI Current Account Superpowers! ',
      image: headerImageArray,
    },
    content: {
      title: (
        <>
          Link your existing <span className="highlight">ICICI current account</span> with our
          banking platform
        </>
      ),
      helpText: (
        <>
          <img src={houseIcon.src} alt={houseIcon.alt} />
          <span> In partnership with </span>
          <img src={iciciBankLogo.src} alt={iciciBankLogo.alt} className="icici-logo" />
        </>
      ),
      description: 'And, reduce your PG pricing to 1.65%* as an exclusive offer! ✨ ',
      featuresList: iciciCAFeaturesList,
      ctaButton: {
        label: 'Link Account Now!',
        onCTAClick: null,
      },
      termsAndConditions:
        '*All details of your ICICI current account will remain 100% confidential and private',
    },
    footer: {
      title: 'Looking to open a new Current Account, instead? We can help you with that too!',
      ctaLabel: 'Apply for new Current Account',
      onCTAClick: null,
    },
  },
};

export { OFFER_DETAILS, headerDivider };
