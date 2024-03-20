import {
  PlaybookContentTypes,
  PlaybookItemsStore,
  ProgramHeader,
  PlaybookItemsStoreInitial,
  ProgramItem,
} from 'merchant/views/PartnerDashboard/PartnerPlaybook/types';

export const introVideoItem: ProgramItem = {
  id: 'introVideoItem',
  content_type: PlaybookContentTypes.VIDEO,
  copy_url: 'https://drive.google.com/file/d/17wiO6tfB9pAPHBQdkj5dfhTAWExCVIkO/view?usp=drive_link',
  preview_url: 'https://drive.google.com/file/d/17wiO6tfB9pAPHBQdkj5dfhTAWExCVIkO/preview',
  description: '',
  download_url: 'https://drive.google.com/uc?export=download&id=17wiO6tfB9pAPHBQdkj5dfhTAWExCVIkO',
  title: 'Introduction to Partner Playbook',
};

const initializeProgramItems = (
  programItemsData: PlaybookItemsStoreInitial,
): PlaybookItemsStore => {
  const initializedData = [] as PlaybookItemsStore;

  programItemsData.forEach(({ sectionKey, sectionItem }) => {
    let totalCount = 0;
    const initializedFolders = sectionItem.folders.map(({ header, items }) => {
      const folderCount = items.length;
      totalCount += folderCount;
      return {
        header,
        items,
        ...(header ? { header: { ...header, total: folderCount } } : {}),
      } as PlaybookItemsStore[0]['sectionItem']['folders'][0];
    });

    initializedData.push({
      sectionKey,
      sectionItem: {
        header: {
          ...sectionItem.header,
          total: totalCount,
        },
        folders: initializedFolders,
      },
    });
  });

  return initializedData;
};

export const programItemsData: PlaybookItemsStore = initializeProgramItems([
  {
    sectionKey: ProgramHeader.get_started,
    sectionItem: {
      header: {
        title: 'Get Started',
        icon: 'CheckCircleIcon',
        hash: 'get-started',
        description: 'Steps and processes to begin your journey as a Razorpay partner',
      },
      folders: [
        {
          header: null,
          items: [
            {
              content_type: PlaybookContentTypes.VIDEO,
              copy_url:
                'https://drive.google.com/file/d/1caBtGV-MAoB3HsbQJ6b0459CUY4of1lm/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1caBtGV-MAoB3HsbQJ6b0459CUY4of1lm/preview',
              description: '4 steps to succeed as a Partner',
              download_url:
                'https://drive.google.com/uc?export=download&id=1caBtGV-MAoB3HsbQJ6b0459CUY4of1lm',
              title: 'Welcome',
            },
            {
              content_type: PlaybookContentTypes.VIDEO,
              copy_url:
                'https://drive.google.com/file/d/1zPDV6m7qK3-v6V9E9Gx3i95hrefVJvqV/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1zPDV6m7qK3-v6V9E9Gx3i95hrefVJvqV/preview',
              description: 'Learn how to add your clients to Razorpay as your referral',
              download_url:
                'https://drive.google.com/uc?export=download&id=1zPDV6m7qK3-v6V9E9Gx3i95hrefVJvqV',
              title: 'Add/Onboard Clients',
            },
            {
              content_type: PlaybookContentTypes.VIDEO,
              copy_url:
                'https://drive.google.com/file/d/1K0DiK2U29IEEh4i2qwYLr5Q3Al2lQJCS/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1K0DiK2U29IEEh4i2qwYLr5Q3Al2lQJCS/preview',
              description:
                'Post adding a referral, complete KYC for your Partner account and become eligible for Partner rewards',
              download_url:
                'https://drive.google.com/uc?export=download&id=1K0DiK2U29IEEh4i2qwYLr5Q3Al2lQJCS',
              title: 'Complete Your KYC',
            },
            {
              content_type: PlaybookContentTypes.VIDEO,
              copy_url:
                'https://drive.google.com/file/d/1M-aFSmVXNc8dTbR_JsRgwGS_E4gE2LXE/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1M-aFSmVXNc8dTbR_JsRgwGS_E4gE2LXE/preview',
              description: "Steps to help you perform KYC for your client's account",
              download_url:
                'https://drive.google.com/uc?export=download&id=1M-aFSmVXNc8dTbR_JsRgwGS_E4gE2LXE',
              title: 'Activate Client Account',
            },
            {
              content_type: PlaybookContentTypes.VIDEO,
              copy_url:
                'https://drive.google.com/file/d/12aEex9mu0MreRGUYOpO537QhU1kdcVH4/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/12aEex9mu0MreRGUYOpO537QhU1kdcVH4/preview',
              description: 'Learn how to settle your partner commission with utmost ease',
              download_url:
                'https://drive.google.com/uc?export=download&id=12aEex9mu0MreRGUYOpO537QhU1kdcVH4',
              title: 'Claim Your Commission',
            },
            {
              content_type: PlaybookContentTypes.VIDEO,
              copy_url:
                'https://drive.google.com/file/d/1zDTgtOM4-YU4d5sRiF55MWxdBhCzjTDC/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1zDTgtOM4-YU4d5sRiF55MWxdBhCzjTDC/preview',
              description: 'Steps to raise support ticket for any query',
              download_url:
                'https://drive.google.com/uc?export=download&id=1zDTgtOM4-YU4d5sRiF55MWxdBhCzjTDC',
              title: 'Ask For Help',
            },
          ],
        },
      ],
    },
  },
  {
    sectionKey: ProgramHeader.grow_your_business,
    sectionItem: {
      header: {
        title: 'Grow Your Business',
        icon: 'TrendingUpIcon',
        hash: 'grow-your-business',
        description:
          'Read to use sales and marketing collaterals to empower you to get more clients as a Razorpay partner',
      },
      folders: [
        {
          header: {
            title: 'Marketing Assets',
            description:
              'Promote yourself as a Razorpay partner with capabilities to help clients with their payments and beyond',
          },
          items: [
            {
              content_type: PlaybookContentTypes.DOC,
              copy_url:
                'https://docs.google.com/document/d/11sByPFUozHkZMcJ8rZISs9RRlXDfO4V6/edit?usp=drive_link',
              preview_url:
                'https://docs.google.com/document/d/e/2PACX-1vSvU7HG_dvxFrHh44TdN6l7eEmTHQWR36_uKvB1Emh0ZeZtv6XMJHuiG3g4RcxB3w/pub?embedded=true',
              description:
                'Ready to use email, whatsapp and message for you to introduce yourself as a Razorpay partner to an e-commerce client',
              download_url:
                'https://drive.google.com/uc?export=download&id=11sByPFUozHkZMcJ8rZISs9RRlXDfO4V6',
              title: 'E-commerce Pitch',
            },
            {
              content_type: PlaybookContentTypes.IMAGE,
              copy_url:
                'https://drive.google.com/file/d/16nFdSO4hvMcomeBNhrbBNONTIMA_QgTh/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/16nFdSO4hvMcomeBNhrbBNONTIMA_QgTh/preview',
              description: 'Ready to use banners for any marketing communications',
              download_url:
                'https://drive.google.com/uc?export=download&id=16nFdSO4hvMcomeBNhrbBNONTIMA_QgTh',
              title: 'E-commerce Banner',
            },
          ],
        },
        {
          header: {
            title: 'Prospecting and Closing',
            description:
              'Portray yourself as an expert at payments by identifying client needs and objections',
          },
          items: [
            {
              content_type: PlaybookContentTypes.PDF,
              copy_url:
                'https://drive.google.com/file/d/1Ae_wF2TE0QM5dL_3v1PV6EEvQ9fhNHPZ/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1Ae_wF2TE0QM5dL_3v1PV6EEvQ9fhNHPZ/preview',
              description:
                'Use this guide to identify the right products for your clients and how to integrate them',
              download_url:
                'https://drive.google.com/uc?export=download&id=1Ae_wF2TE0QM5dL_3v1PV6EEvQ9fhNHPZ',
              title: 'Product-Client Mapping',
              disable_download: true,
            },
            {
              content_type: PlaybookContentTypes.PDF,
              copy_url:
                'https://drive.google.com/file/d/1RgaTRwBxqYTsecFajfhzQdkbWcmu-xAv/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1RgaTRwBxqYTsecFajfhzQdkbWcmu-xAv/preview',
              description:
                'Close deals easily by smartly handling client objections during the sales pitch',
              download_url:
                'https://drive.google.com/uc?export=download&id=1RgaTRwBxqYTsecFajfhzQdkbWcmu-xAv',
              title: 'Objection Handling',
              disable_download: true,
            },
          ],
        },
        {
          header: {
            title: 'Pitch decks',
            description:
              'Make compelling sales presentations using industry specific partner decks',
          },
          items: [
            {
              content_type: PlaybookContentTypes.PPT,
              copy_url:
                'https://docs.google.com/presentation/d/16q5yxq5WP4ia1EF7g7MlVY6YGfOZSc5k/edit?usp=drive_link&ouid=111736103671181464456&rtpof=true&sd=true',
              preview_url:
                'https://docs.google.com/presentation/d/e/2PACX-1vRZiLW3gIiFR-OfA6VhjkX06s1Jrj8VGYYCkF7jqT-QtJiHzlX_wRS1Q0flT3XcnA/embed?start=false&loop=false&delayms=10000',
              description: 'A custom partner pitch deck for e-commerce clients',
              download_url:
                'https://drive.google.com/uc?export=download&id=16q5yxq5WP4ia1EF7g7MlVY6YGfOZSc5k',
              title: 'E-commerce Presentation',
            },
          ],
        },
        {
          header: {
            title: 'Product Brochures',
            description:
              'Let your clients see the detailed features of different Razorpay products and choose what fits their business needs',
          },
          items: [
            {
              content_type: PlaybookContentTypes.PDF,
              copy_url:
                'https://drive.google.com/file/d/1x4TGqsWV8VbnfH276pCCA0b79wG1CdCn/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1x4TGqsWV8VbnfH276pCCA0b79wG1CdCn/preview',
              description:
                'Provide secure and seamless pathway for your client to accept digital payments',
              download_url:
                'https://drive.google.com/uc?export=download&id=1x4TGqsWV8VbnfH276pCCA0b79wG1CdCn',
              title: 'Payment Gateway',
            },
            {
              content_type: PlaybookContentTypes.PDF,
              copy_url:
                'https://drive.google.com/file/d/11i2bWcHcki7jOEgKQF0-_w2i_QvlyS1u/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/11i2bWcHcki7jOEgKQF0-_w2i_QvlyS1u/preview',
              description: "Take your client's business global",
              download_url:
                'https://drive.google.com/uc?export=download&id=11i2bWcHcki7jOEgKQF0-_w2i_QvlyS1u',
              title: 'International Payments',
            },
            {
              content_type: PlaybookContentTypes.PDF,
              copy_url:
                'https://drive.google.com/file/d/1anLmaS-PsY1c7KyPY548QWSOIagwM4sc/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1anLmaS-PsY1c7KyPY548QWSOIagwM4sc/preview',
              description: 'Enable your clients to accept payments on WhatsApp Business',
              download_url:
                'https://drive.google.com/uc?export=download&id=1anLmaS-PsY1c7KyPY548QWSOIagwM4sc',
              title: 'Razorpay Payments for Whatsapp',
            },
            {
              content_type: PlaybookContentTypes.PDF,
              copy_url:
                'https://drive.google.com/file/d/11Hs5iIrmi-7K6my5zuIeuYaGRX2unVyr/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/11Hs5iIrmi-7K6my5zuIeuYaGRX2unVyr/preview',
              description: 'Increase average basket size for your clients',
              download_url:
                'https://drive.google.com/uc?export=download&id=11Hs5iIrmi-7K6my5zuIeuYaGRX2unVyr',
              title: 'Affordability',
            },
            {
              content_type: PlaybookContentTypes.PDF,
              copy_url:
                'https://drive.google.com/file/d/1CmcTGe5QObfdp_KDCdcdfBxpgsvbq_3L/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1CmcTGe5QObfdp_KDCdcdfBxpgsvbq_3L/preview',
              description:
                'Provide faster checkout with reduced RTO/COD risk for your e-commerce clients',
              download_url:
                'https://drive.google.com/uc?export=download&id=1CmcTGe5QObfdp_KDCdcdfBxpgsvbq_3L',
              title: 'Magic Checkout',
            },
          ],
        },
        {
          header: {
            title: 'Product Videos',
            description: 'Help your clients better visualize Razorpay products and their benefits',
          },
          items: [
            {
              content_type: PlaybookContentTypes.VIDEO,
              copy_url:
                'https://drive.google.com/file/d/1jStwVuIqOJ27aRqGe5jIzyYRnaJ-3NR2/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1jStwVuIqOJ27aRqGe5jIzyYRnaJ-3NR2/preview',
              description: "Take your client's business global",
              download_url:
                'https://drive.google.com/uc?export=download&id=1jStwVuIqOJ27aRqGe5jIzyYRnaJ-3NR2',
              title: 'International Payments',
            },
            {
              content_type: PlaybookContentTypes.VIDEO,
              copy_url:
                'https://drive.google.com/file/d/1BWbrBT3Lef2SV4RkGKY0mFA1PoQm7A9D/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1BWbrBT3Lef2SV4RkGKY0mFA1PoQm7A9D/preview',
              description: 'Enable your clients to accept payments on WhatsApp Business',
              download_url:
                'https://drive.google.com/uc?export=download&id=1BWbrBT3Lef2SV4RkGKY0mFA1PoQm7A9D',
              title: 'Razorpay - Payments for WhatsApp',
            },
            {
              content_type: PlaybookContentTypes.VIDEO,
              copy_url:
                'https://drive.google.com/file/d/1yMZCoTBmlqs8dq3zjH1RUz62OjBTWKpX/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1yMZCoTBmlqs8dq3zjH1RUz62OjBTWKpX/preview',
              description: 'Increase average basket size for your clients',
              download_url:
                'https://drive.google.com/uc?export=download&id=1yMZCoTBmlqs8dq3zjH1RUz62OjBTWKpX',
              title: 'Affordability',
            },
            {
              content_type: PlaybookContentTypes.VIDEO,
              copy_url:
                'https://drive.google.com/file/d/1bs3ydMurM22C1m4pNuh1cSrtyV3_ez2-/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1bs3ydMurM22C1m4pNuh1cSrtyV3_ez2-/preview',
              description:
                'Provide faster checkout with reduced RTO/COD risk for your e-commerce clients',
              download_url:
                'https://drive.google.com/uc?export=download&id=1bs3ydMurM22C1m4pNuh1cSrtyV3_ez2-',
              title: 'Magic Checkout',
            },
          ],
        },
      ],
    },
  },
  {
    sectionKey: ProgramHeader.help_and_support,
    sectionItem: {
      header: {
        title: 'Help & Support',
        icon: 'HeadphonesIcon',
        hash: 'help-and-support',
        description: "Be your client's most reliable support system for all things Razorpay",
      },

      folders: [
        {
          header: {
            title: 'Dashboard/Report Queries',
            description: 'Show your clients how to navigate Razorpay dashboards with utmost ease',
          },
          items: [
            {
              content_type: PlaybookContentTypes.VIDEO,
              copy_url:
                'https://drive.google.com/file/d/1NSeJjO_HUQp7uc2OvunL9HrzTEi-iYx1/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1NSeJjO_HUQp7uc2OvunL9HrzTEi-iYx1/preview',
              description:
                'Train your clients to use Razorpay payments dashboard effectively for tracking all their transactions and settlements',
              download_url:
                'https://drive.google.com/uc?export=download&id=1NSeJjO_HUQp7uc2OvunL9HrzTEi-iYx1',
              title: 'Payments Dashboard',
            },
          ],
        },
        {
          header: {
            title: 'KYC Queries',
            description: 'Assist your clients with KYC to ensure smooth onboarding',
          },
          items: [
            {
              content_type: PlaybookContentTypes.VIDEO,
              copy_url:
                'https://drive.google.com/file/d/1gh2ijWDA7K12ws2a5jDlYh2ENMwFneEW/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1gh2ijWDA7K12ws2a5jDlYh2ENMwFneEW/preview',
              description: "Steps to help you perform KYC for your client's account",
              download_url:
                'https://drive.google.com/uc?export=download&id=1gh2ijWDA7K12ws2a5jDlYh2ENMwFneEW',
              title: 'KYC Completion',
            },
            {
              content_type: PlaybookContentTypes.PDF,
              copy_url:
                'https://drive.google.com/file/d/1K2imMifxgSKaMQgQ8ksgfft0uzIIjpzA/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1K2imMifxgSKaMQgQ8ksgfft0uzIIjpzA/preview',
              description:
                'Assist your clients on how to fill up their KYC form effectively and avoid any delays',
              download_url:
                'https://drive.google.com/uc?export=download&id=1K2imMifxgSKaMQgQ8ksgfft0uzIIjpzA',
              title: 'KYC Checklist',
              disable_download: true,
            },
          ],
        },
        {
          header: {
            title: 'Product Queries',
            description:
              'Explain clients the eligibility criteria and process for getting any Razorpay product',
          },
          items: [
            {
              content_type: PlaybookContentTypes.PDF,
              copy_url:
                'https://drive.google.com/file/d/1XD3VFihFd9LMeUKYgAailMGWmK3ZhZuA/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1XD3VFihFd9LMeUKYgAailMGWmK3ZhZuA/preview',
              description:
                'Clarify client doubt related to qualifiers and processes for enabling international payments',
              download_url:
                'https://drive.google.com/uc?export=download&id=1XD3VFihFd9LMeUKYgAailMGWmK3ZhZuA',
              title: 'International Payments Enablement',
              disable_download: true,
            },
          ],
        },
      ],
    },
  },
]);
