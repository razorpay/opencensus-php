import {
  PlaybookContentTypes,
  PlaybookItemsStore,
  ProgramHeader,
} from 'merchant/views/PartnerDashboard/PartnerPlaybook/types';

export const INTRO_VIDEO_EMBED_LINK =
  'https://www.youtube.com/embed/jO_2m3RgiKw?si=JjT95vCTUV77j5E-';

export const programItemsData: PlaybookItemsStore = [
  {
    sectionKey: ProgramHeader.get_started,
    sectionItem: {
      header: {
        title: 'Get Started',
        icon: 'CheckCircleIcon',
        hash: 'get-started',
        description: 'Steps and processes to begin your journey as a Razorpay partner',
        total: 6,
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
              description: '5 steps to being a successful partner',
              download_url:
                'https://drive.google.com/uc?export=download&id=1caBtGV-MAoB3HsbQJ6b0459CUY4of1lm',
              title: 'Welcome',
            },
            {
              content_type: PlaybookContentTypes.VIDEO,
              copy_url:
                'https://drive.google.com/file/d/1K0DiK2U29IEEh4i2qwYLr5Q3Al2lQJCS/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1K0DiK2U29IEEh4i2qwYLr5Q3Al2lQJCS/preview',
              description:
                'Steps to help you complete KYC for your partner account and become eligible for partner rewards',
              download_url:
                'https://drive.google.com/uc?export=download&id=1K0DiK2U29IEEh4i2qwYLr5Q3Al2lQJCS',
              title: 'Complete Your KYC',
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
              description: 'Learn how to settle your partner commission with utmost ease ',
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
              title: 'Ask For Help ',
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
        total: 20,
      },
      folders: [
        {
          header: {
            title: 'Marketing Assets',
            description:
              'Promote yourself as a Razorpay partner with capabilities to help clients with their payments and beyond',
            total: 8,
          },
          items: [
            {
              content_type: PlaybookContentTypes.PDF,
              copy_url:
                'https://drive.google.com/file/d/13GtVRh0IlDfvaewkGXItFUAQj5N80KGW/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/13GtVRh0IlDfvaewkGXItFUAQj5N80KGW/preview',
              description: 'Access Razorpay logo usage guidelines for different purposes',
              download_url:
                'https://drive.google.com/uc?export=download&id=13GtVRh0IlDfvaewkGXItFUAQj5N80KGW',
              title: 'Razorpay Logo',
            },

            {
              content_type: PlaybookContentTypes.PDF,
              copy_url:
                'https://drive.google.com/file/d/1s09NX21kAizDJ-gn_-nAaAlhnw6OsMcN/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1s09NX21kAizDJ-gn_-nAaAlhnw6OsMcN/preview',
              description:
                'A ready to use page that can be published on your website to promote our partnership to your prospective clients',
              download_url:
                'https://drive.google.com/uc?export=download&id=1s09NX21kAizDJ-gn_-nAaAlhnw6OsMcN',
              title: 'Website Listing',
            },
            {
              content_type: PlaybookContentTypes.DOC,
              copy_url:
                'https://docs.google.com/document/d/11sByPFUozHkZMcJ8rZISs9RRlXDfO4V6/edit?usp=drive_link',
              preview_url:
                'https://docs.google.com/document/d/e/2PACX-1vSvU7HG_dvxFrHh44TdN6l7eEmTHQWR36_uKvB1Emh0ZeZtv6XMJHuiG3g4RcxB3w/pub?embedded=true',
              description:
                'Ready to use email, whatsapp and message for you to introduce yourself as a Razorpay partner to an ecommerce client',
              download_url:
                'https://drive.google.com/uc?export=download&id=11sByPFUozHkZMcJ8rZISs9RRlXDfO4V6',
              title: 'Ecommerce Pitch',
            },
            {
              content_type: PlaybookContentTypes.DOC,
              copy_url:
                'https://docs.google.com/document/d/1Do1Qf45J5eB0jPaK0_vEx64wHos6djmp/view?usp=drive_link',
              preview_url:
                'https://docs.google.com/document/d/e/2PACX-1vQTEdkIrobJOEpapP2x8cldiiIXmaLbK5hkPX-2xAE7Ax6TRd9aNItRtMFuYF1Uog/pub?embedded=true',
              description:
                'Ready to use email, whatsapp and message for you to introduce yourself as a Razorpay partner to an ed-tech client',
              download_url:
                'https://drive.google.com/uc?export=download&id=1Do1Qf45J5eB0jPaK0_vEx64wHos6djmp',
              title: 'Ed-tech Pitch',
            },
            {
              content_type: PlaybookContentTypes.DOC,
              copy_url:
                'https://docs.google.com/document/d/1F2Sl1FHkJ9cMUDbv86XROfW2CRxI6PzS/edit?usp=drive_link',
              preview_url:
                'https://docs.google.com/document/d/e/2PACX-1vTiALfs32sK7ej5WwycKGJFfDTt7wp8lll-W1cH_SDEWVbCgDQrXkByew0k5xrY9Q/pub?embedded=true',
              description:
                'Ready to use email, whatsapp and linkedin message for you to introduce yourself as a Razorpay partner to a BFSI client',
              download_url:
                'https://drive.google.com/uc?export=download&id=1F2Sl1FHkJ9cMUDbv86XROfW2CRxI6PzS',
              title: 'Financial Services Pitch',
            },
            {
              content_type: PlaybookContentTypes.IMAGE,
              copy_url:
                'https://drive.google.com/file/d/16nFdSO4hvMcomeBNhrbBNONTIMA_QgTh/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/16nFdSO4hvMcomeBNhrbBNONTIMA_QgTh/preview',
              description: '',
              download_url:
                'https://drive.google.com/uc?export=download&id=16nFdSO4hvMcomeBNhrbBNONTIMA_QgTh',
              title: 'Ecommerce Banner',
            },
            {
              content_type: PlaybookContentTypes.IMAGE,
              copy_url:
                'https://drive.google.com/file/d/1T1lsZy1F-3VKJjdXWGKEVvCy9ER1oezm/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1T1lsZy1F-3VKJjdXWGKEVvCy9ER1oezm/preview',
              description: '',
              download_url:
                'https://drive.google.com/uc?export=download&id=1T1lsZy1F-3VKJjdXWGKEVvCy9ER1oezm',
              title: 'Education Banner',
            },
            {
              content_type: PlaybookContentTypes.IMAGE,
              copy_url:
                'https://drive.google.com/file/d/1Oy5jcOs4F3zxC5BQE-qkEaOMIGiPqEKX/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1Oy5jcOs4F3zxC5BQE-qkEaOMIGiPqEKX/preview',
              description: '',
              download_url:
                'https://drive.google.com/uc?export=download&id=1Oy5jcOs4F3zxC5BQE-qkEaOMIGiPqEKX',
              title: 'Financial Services Banner',
            },
          ],
        },
        {
          header: {
            title: 'Pitch decks',
            description:
              'Make compelling sales presentations using industry specific partner decks',
            total: 3,
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
              title: 'Ecommerce Presentation',
            },
            {
              content_type: PlaybookContentTypes.PPT,
              copy_url:
                'https://docs.google.com/presentation/d/1Q_MsNkYgI5CejjZgSFCvfJas3z-6B7KI/edit?usp=drive_link&ouid=111736103671181464456&rtpof=true&sd=true',
              preview_url:
                'https://docs.google.com/presentation/d/e/2PACX-1vTU4qjw8ImC8PqVjW-UAThrrDBmLSUOlRKDOaWcb7pMW7rA06mvh8A6J2QL_6gBMQ/embed?start=false&loop=false&delayms=10000',
              description: 'A custom partner pitch deck for ed-tech clients',
              download_url:
                'https://drive.google.com/uc?export=download&id=1Q_MsNkYgI5CejjZgSFCvfJas3z-6B7KI',
              title: 'Ed-tech Presentation',
            },
            {
              content_type: PlaybookContentTypes.PPT,
              copy_url:
                'https://docs.google.com/presentation/d/1H3N7Y-ck239-HJ1GjCCMbKhg0SdXNjqQ/edit?usp=drive_link&ouid=111736103671181464456&rtpof=true&sd=true',
              preview_url:
                'https://docs.google.com/presentation/d/e/2PACX-1vTN-B_N3tU2F0XR3nXH8WL7ygDAc29K9bNYx5zxtQ_BUJFD8K2qXGfZ_X80rd73OA/embed?start=false&loop=true&delayms=10000',
              description: 'A custom partner pitch deck for BFSI clients',
              download_url:
                'https://drive.google.com/uc?export=download&id=1H3N7Y-ck239-HJ1GjCCMbKhg0SdXNjqQ',
              title: 'Financial Services Presentation',
            },
          ],
        },
        {
          header: {
            title: 'Product Brochures',
            description:
              'Let your clients see the detailed features of different Razorpay products and choose what fits their business needs',
            total: 4,
          },
          items: [
            {
              content_type: PlaybookContentTypes.PDF,
              copy_url:
                'https://drive.google.com/file/d/1x4TGqsWV8VbnfH276pCCA0b79wG1CdCn/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1x4TGqsWV8VbnfH276pCCA0b79wG1CdCn/preview',
              description: '',
              download_url:
                'https://drive.google.com/uc?export=download&id=1x4TGqsWV8VbnfH276pCCA0b79wG1CdCn',
              title: 'Payment Gateway',
            },
            {
              content_type: PlaybookContentTypes.PDF,
              copy_url:
                'https://drive.google.com/file/d/11Hs5iIrmi-7K6my5zuIeuYaGRX2unVyr/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/11Hs5iIrmi-7K6my5zuIeuYaGRX2unVyr/preview',
              description: 'Solution to increase average basket size for your clients',
              download_url:
                'https://drive.google.com/uc?export=download&id=11Hs5iIrmi-7K6my5zuIeuYaGRX2unVyr',
              title: 'Affordability',
            },
            {
              content_type: PlaybookContentTypes.PDF,
              copy_url:
                'https://drive.google.com/file/d/11i2bWcHcki7jOEgKQF0-_w2i_QvlyS1u/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/11i2bWcHcki7jOEgKQF0-_w2i_QvlyS1u/preview',
              description: '',
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
              description: '',
              download_url:
                'https://drive.google.com/uc?export=download&id=1anLmaS-PsY1c7KyPY548QWSOIagwM4sc',
              title: 'Razorpay Payments for Whatsapp',
            },
            {
              content_type: PlaybookContentTypes.PDF,
              copy_url:
                'https://drive.google.com/file/d/1CmcTGe5QObfdp_KDCdcdfBxpgsvbq_3L/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1CmcTGe5QObfdp_KDCdcdfBxpgsvbq_3L/preview',
              description: '',
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
            total: 3,
          },
          items: [
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
                'https://drive.google.com/file/d/1bs3ydMurM22C1m4pNuh1cSrtyV3_ez2-/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1bs3ydMurM22C1m4pNuh1cSrtyV3_ez2-/preview',
              description:
                'Provide faster checkout with reduced RTO/COD risk for your ecommerce clients',
              download_url:
                'https://drive.google.com/uc?export=download&id=1bs3ydMurM22C1m4pNuh1cSrtyV3_ez2-',
              title: 'Magic Checkout',
            },
          ],
        },
        {
          header: {
            title: 'Prospecting and Closing',
            description:
              'Portray yourself as an expert at payments by identifying client needs and objections',
            total: 2,
          },
          items: [
            {
              content_type: PlaybookContentTypes.PDF,
              copy_url:
                'https://drive.google.com/file/d/17tX6UIkRpa-Gpg5MXvdLATpour3r9aqc/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/10-esdDG6DSk0TiG5aT87QSb6IT2FTJen/preview',
              description:
                'Use this chart to identify the right products for your clients or vice versa',
              download_url:
                'https://drive.google.com/uc?export=download&id=10-esdDG6DSk0TiG5aT87QSb6IT2FTJen',
              title: 'Product-Client Mapping',
            },
            {
              content_type: PlaybookContentTypes.PDF,
              copy_url:
                'https://drive.google.com/file/d/17tX6UIkRpa-Gpg5MXvdLATpour3r9aqc/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/17tX6UIkRpa-Gpg5MXvdLATpour3r9aqc/preview',
              description:
                'Close deals easily by smartly handling client objections during the sales pitch ',
              download_url:
                'https://drive.google.com/uc?export=download&id=17tX6UIkRpa-Gpg5MXvdLATpour3r9aqc',
              title: 'Objection Handling',
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
        total: 4,
      },

      folders: [
        {
          header: {
            title: 'Dashboard/Report Queries',
            description: '',
            total: 1,
          },
          items: [
            {
              content_type: PlaybookContentTypes.VIDEO,
              copy_url:
                'https://drive.google.com/file/d/1QCQo3VWOGlNkXlQvPc_ERu4NjLgH1K5Z/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1QCQo3VWOGlNkXlQvPc_ERu4NjLgH1K5Z/preview',
              description:
                'Train your clients to use Razorpay payments dashboard effectively for tracking all their transactions and settlements ',
              download_url:
                'https://drive.google.com/uc?export=download&id=1QCQo3VWOGlNkXlQvPc_ERu4NjLgH1K5Z',
              title: 'Payments Dashboard',
            },
          ],
        },
        {
          header: {
            title: 'KYC Queries',
            description: '',
            total: 2,
          },
          items: [
            {
              content_type: PlaybookContentTypes.VIDEO,
              copy_url:
                'https://drive.google.com/file/d/1gh2ijWDA7K12ws2a5jDlYh2ENMwFneEW/view?usp=drive_link',
              preview_url:
                'https://drive.google.com/file/d/1gh2ijWDA7K12ws2a5jDlYh2ENMwFneEW/preview',
              description: 'Assist your clients with KYC to ensure smooth onboarding',
              download_url:
                'https://drive.google.com/uc?export=download&id=1gh2ijWDA7K12ws2a5jDlYh2ENMwFneEW',
              title: 'KYC Completion',
            },
            {
              content_type: PlaybookContentTypes.DOC,
              copy_url:
                'https://docs.google.com/document/d/1UKVJYN1-4HTHxrdLY_L6WVZ53AaLRnXF/edit?usp=drive_link&ouid=111736103671181464456&rtpof=true&sd=true',
              preview_url:
                'https://docs.google.com/document/d/e/2PACX-1vTSDRAEUZBvlOqcnOgRTkEvqJghWCSwGk62mJ5vqt0sFmEkOdBn6DNYkJLFuXfcbA/pub',
              description:
                'Assist your clients on how to fill up their KYC form effectively and avoid any delays',
              download_url:
                'https://drive.google.com/uc?export=download&id=1UKVJYN1-4HTHxrdLY_L6WVZ53AaLRnXF',
              title: 'KYC Checklist',
            },
          ],
        },
        {
          header: {
            title: 'Product Queries',
            description: '',
            total: 1,
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
            },
          ],
        },
      ],
    },
  },
];
