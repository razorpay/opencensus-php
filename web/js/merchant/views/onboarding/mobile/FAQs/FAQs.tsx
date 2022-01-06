import React, { useRef, useLayoutEffect, useEffect } from 'react';
import styled from 'styled-components';
import Link from '@razorpay/blade-old/src/atoms/Link';
import Text from '@razorpay/blade-old/src/atoms/Text';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Heading from '@razorpay/blade-old/src/atoms/Heading';
import { getColor } from '@razorpay/blade-old/src/_helpers/theme';
import { Modal, ModalBody } from 'common/components/Modal';
import Panel from 'common/components/Accordian/Panel';
import StatelessAccordian from 'common/components/Accordian/StatelessAccordian';
import { useActivationFormState } from '../context/store';
import useTrackEvents from 'merchant/hooks/useTrackEvents';

const StyledSeparator = styled(View)`
  height: 1px;
  width: 100%;
  background-color: ${({ theme }) => getColor(theme, 'shade.920')};
`;

interface IFAQsProps {
  activeTab?: string;
}

const FAQs = ({ activeTab }: IFAQsProps): React.ReactElement => {
  const isOpen = useActivationFormState((state) => state.isFAQOpen);
  const setIsOpen = useActivationFormState((state) => state.setIsFAQOpen);
  const sectionToDisplay = useActivationFormState((state) => state.fAQSection);
  const websiteDetailsRef = useRef<HTMLDivElement>(null);
  const modalBottomSheetRef = useRef<HTMLDivElement>(null);
  const [expanded, setExpanded] = React.useState<React.ReactText[]>([sectionToDisplay]);
  const setFAQSection = useActivationFormState((state) => state.setFAQSection);
  const trackEvents = useTrackEvents();

  const questionsMapping = {
    Q1: 'What is Billing label?',
    Q2: 'How can I add api keys to my website?',
    Q3: 'What are the documents needed to sign-up?',
    Q4: 'I have submitted my activation form. When will my account get activated?',
    Q5: 'What is KYC review process',
    Q6: 'Where do I find the templates for website policies?',
    Q7: 'Special anniversary pricing offer',
    Q8: 'Unlock Growth with Razorpay',
    Q9: 'I have signed up with Razorpay. How do I complete my activation form?',
    Q10: 'My account is not yet activated, its been too long since I got any update, what do I do?',
    Q11:
      'I have submitted my activation form but my account is not activated. Can I start integrating?',
    Q12: 'I had submitted the form long back. How do I get my account activated?',
    Q13: 'Do you support unregistered businesses?',
    Q14: 'What are the payment methods supported?',
  };

  const sendEventToSegment = (key) => {
    let isCurrentAccordianOpen;
    if (expanded[0] === '' || key !== expanded[0] || expanded.length === 0) {
      isCurrentAccordianOpen = true;
    } else isCurrentAccordianOpen = false;

    const isPreviousAccordianClosed =
      key !== expanded[0] && expanded.length !== 0 && expanded[0] !== '';

    if (isCurrentAccordianOpen) {
      trackEvents({
        objectName: 'Accordian',
        actionName: 'Opened',
        screen: 'home page',
        properties: {
          'Accordian Label': questionsMapping[key],
          Screen: activeTab,
        },
      });
    } else {
      trackEvents({
        objectName: 'Accordian',
        actionName: 'Closed',
        screen: 'home page',
        properties: {
          'Accordian Label': questionsMapping[key],
          Screen: activeTab,
        },
      });
      trackEvents({
        objectName: 'Screen',
        actionName: 'Viewed',
        screen: 'home page',
        properties: {
          'Accordian Label': questionsMapping[key],
          Screen: activeTab,
        },
      });
    }
    if (isPreviousAccordianClosed) {
      trackEvents({
        objectName: 'Accordian',
        actionName: 'Closed',
        screen: 'home page',
        properties: {
          'Accordian Label': questionsMapping[expanded[0]],
          Screen: activeTab,
        },
      });
    }
  };

  useLayoutEffect(() => {
    setTimeout(() => {
      if (modalBottomSheetRef.current) {
        if (sectionToDisplay === 'Q2' && websiteDetailsRef.current) {
          // calculate distance between top of modal and the panel element
          const yDistance =
            websiteDetailsRef.current.getBoundingClientRect().y -
            modalBottomSheetRef.current.getBoundingClientRect().y;
          // height of Bottom Sheet Header in Modal
          const headerEl = document.getElementById('bottomSheetHeader');
          const headerHeight = headerEl ? Math.ceil(headerEl.getBoundingClientRect().height) : 0;

          modalBottomSheetRef.current.scrollTop = yDistance - headerHeight;
        }
      }
    });
    setExpanded([sectionToDisplay]);
  }, [sectionToDisplay]);

  useEffect(() => {
    if (isOpen) {
      trackEvents({
        objectName: 'Bottom Sheet',
        actionName: 'Loaded',
        screen: 'home page',
        properties: {
          'Modal Label': 'FAQs',
        },
      });
    }
  }, [isOpen]);
  return (
    <Modal
      bottomsheet
      isOpen={isOpen}
      onClose={() => {
        trackEvents({
          objectName: 'Bottom sheet',
          actionName: 'Closed',
          screen: 'home page',
          properties: {
            'Modal Label': 'FAQs',
          },
        });
        setIsOpen(false);
        setFAQSection('');
      }}
      closeable={true}
      bottomSheetHeaderText="FAQS"
      bottomSheetRef={modalBottomSheetRef}
    >
      <>
        <Space margin={[0.75, 0, 1.5, 0]}>
          <StyledSeparator />
        </Space>
        <ModalBody>
          <StatelessAccordian accordian expanded={expanded} onChange={(_, exp) => setExpanded(exp)}>
            <Space padding={[0, 0, 0.5]}>
              <View>
                <Heading size="medium" color="shade.970">
                  Billing label
                </Heading>
              </View>
            </Space>
            <Panel
              key="Q1"
              title="What is Billing label?"
              onClick={() => {
                sendEventToSegment('Q1');
              }}
            >
              Billing label is your brand&apos;s identity, it will be displayed on your invoices and
              bills. Please ensure billing label is as close to your business name/website as
              possible.
            </Panel>
            <Space padding={[1.5, 0, 0.5]}>
              <View ref={websiteDetailsRef}>
                <Heading size="medium" color="shade.970">
                  Website Details
                </Heading>
              </View>
            </Space>
            <Panel
              key="Q2"
              title="How can I add api keys to my website?"
              onClick={() => {
                sendEventToSegment('Q2');
              }}
            >
              Following are the mandatory requirements to access and api keys to your website:
              <br />
              1.Privacy Policy page
              <br />
              2.Terms and Conditions
              <br />
              3.Refund Policy
              <br />
              4. Products and services sold on your website should match your business category and
              sub-category entered on Razorpay Once the above requirements are met, we will grant
              you live api keys.
            </Panel>
            <Space padding={[1.5, 0, 0.5]}>
              <View>
                <Heading size="medium" color="shade.970">
                  KYC Queries
                </Heading>
              </View>
            </Space>
            <Panel
              key="Q3"
              title="What are the documents needed to sign-up?"
              onClick={() => {
                sendEventToSegment('Q3');
              }}
            >
              Here are the Documents to be uploaded:
              <br />
              <br />
              Documents for registered business:
              <ul>
                <li>
                  One Business Proof (Certificate of Incorporation, Partnership deed, Service tax,
                  GST registration document);
                </li>
                <li>
                  Firm/Company PAN (Not for Proprietorship), Promoter’s PAN (Proprietor/director),
                </li>
                <li>Cancelled cheque / Bank account statement in the name of the business, </li>
                <li>Authorized signatory address proof (Passport/AADHAR/Voter ID)</li>
              </ul>
              <br />
              <br />
              Documents for unregistered/freelancer/individual businesses:
              <ul>
                <li>Identity Proof - PAN Card</li>
                <li>Proof of Address - Any of Passport/Aadhar/Voter ID</li>
              </ul>
            </Panel>
            <Panel
              key="Q4"
              title="I have submitted my activation form. When will my account get activated?"
              onClick={() => {
                sendEventToSegment('Q4');
              }}
            >
              Activation of an account is subject to approval from our banking partners (Working
              days do not include Saturdays, Sundays and bank holidays)
              <br />
              Our team will update you on the status of your account, once we get a revert from our
              bank.
            </Panel>
            <Panel
              key="Q5"
              title="What is KYC review process"
              onClick={() => {
                sendEventToSegment('Q5');
              }}
            >
              <Text size="medium" weight="bold" style={{ display: 'inline' }} color="shade.970">
                Instant Activation: &nbsp;
              </Text>
              We understand the urgency to start accepting payments and hence instantly activate
              accounts based on certain criteria.
              <br />
              <br />
              <Text size="medium" weight="bold" style={{ display: 'inline' }} color="shade.970">
                KYC form submission: &nbsp;
              </Text>
              While you can start accepting payments form your customers as your account was
              instantly activated, it is required that you submit your KYC form which needs to be
              reviewed and approved to enable settlements for your account.
              <br />
              <br />
              <Text size="medium" weight="bold" style={{ display: 'inline' }} color="shade.970">
                KYC form review: &nbsp;
              </Text>
              The TAT for KYC review is 2 working days [as per the settlement schedule T+2] from the
              date of the first transaction\date of KYC form submission in the same order or
              priority
              <br />
              <br />
              <Text size="medium" weight="bold" style={{ display: 'inline' }} color="shade.970">
                KYC form not approved: &nbsp;
              </Text>
              Settlements enablement is subject to KYC form approval, in the event of KYC form
              rejections funds will remain on hold for 120 days [chargeback period] and released
              after 120 days if there are no chargebacks or fraud reported.
            </Panel>
            <Space padding={[1.5, 0, 0.5]}>
              <View>
                <Heading size="medium" color="shade.970">
                  Website
                </Heading>
              </View>
            </Space>
            <Panel
              key="Q6"
              title="Where do I find the templates for website policies?"
              onClick={() => {
                sendEventToSegment('Q6');
              }}
            >
              Please find the sample templates for website policies here:
              <br />
              <br />
              <ul>
                <li>
                  <Link href="https://docs.google.com/document/d/1MpaLoEbx5-cmTB3qfbDadhjsuNCkEA2j13H2ZGx3PAk/edit">
                    Privacy Policy
                  </Link>
                </li>
                <li>
                  <Link href="https://docs.google.com/document/d/1zOrg11NPYSMCxa3KwkOnfPxKRkllzXBdocEQsQN10TM/edit">
                    Refund Policy
                  </Link>
                </li>
                <li>
                  <Link href="https://docs.google.com/document/d/1wYq6CULlAtBdYrcjEVygQ3uYOplqU-kT1wrxguUKHcI/edit">
                    Terms and Conditions
                  </Link>
                </li>
              </ul>
            </Panel>
            <Space padding={[1.5, 0, 0.5]}>
              <View>
                <Heading size="medium" color="shade.970">
                  Offers
                </Heading>
              </View>
            </Space>
            <Panel
              key="Q7"
              title="Special anniversary pricing offer"
              onClick={() => {
                sendEventToSegment('Q7');
              }}
            >
              <Text weight="bold" color="shade.970" size="medium">
                1) What’s the eligibility criteria for the promotional pricing offer?
              </Text>
              The promotional pricing is only applicable for businesses (registered or unregistered)
              who are signing up on Razorpay after 4 March 00:00 AM and before 30 April 11:59 PM.
              <br />
              <br />
              <Text weight="bold" color="shade.970" size="medium">
                2) What’s the timeframe for the promotional pricing offer?
              </Text>
              The promotional pricing is applicable for all transactions done by your end customers
              between 4th March 00:00 AM till 30th April 11:59 PM. After the expiry of the
              promotional pricing offer, all transactions will be charged at our standard pricing
              plan of 2%.
              <br />
              <br />
              <Text weight="bold" color="shade.970" size="medium">
                3)What payment modes does the promotional pricing cover?
              </Text>
              The payment modes covered in the promotional pricing are -
              <img
                src="https://s3.amazonaws.com/cdn.freshdesk.com/data/helpdesk/attachments/production/11056260169/original/g6lleUQWYfwihRyJhTuOO_4yvbiJF6rV4A.png?1583259882"
                style={{ display: 'block', width: '100%' }}
              />
              <small>
                *Some payment methods might take time to get enabled and are subjected to the
                approval of our wallet/banking partners.
              </small>
              <br />
              <br />
              <Text weight="bold" color="shade.970" size="medium">
                4) What products does the promotional pricing cover?
              </Text>
              The promotion covers transactions done via Payment Gateway, Payment Links, Payment
              Pages, Invoices, BharatQR, ePOS app and Subscriptions. Transactions done via Smart
              Collect (Bank transfers) are not included in the promotional pricing. Any transactions
              done under Subscriptions via Credit Card will reflect the promotional pricing during
              the course of the promotional offer. Standard pricing will be applied afterwards.
              <br />
              <br />
              <Text weight="bold" color="shade.970" size="medium">
                5) Is the offer applicable to existing / old merchants?
              </Text>
              No, the promotional pricing is only offered to new businesses who sign up on Razorpay
              during the period between 4 March 00:00 AM and 30 April 11:59 PM.
              <br />
              However, if you are an existing merchant of Razorpay currently on our Standard pricing
              (2.0%), you can fill this{' '}
              <Link href="https://razorpay.typeform.com/to/giXuFa">quick survey</Link> and we will
              get the pricing changed for you in 1-2 working days. The other terms and conditions
              for the promotional pricing will apply to you as well.
              <br />
              <br />
              <Text weight="bold" color="shade.970" size="medium">
                6) Where can I see the updated pricing for my account?
              </Text>
              The promotional pricing can be seen in the Razorpay Dashboard under the Reports
              section. Any payments reports downloaded with fees breakup will show the effective
              pricing in each transaction row.
            </Panel>
            <Panel
              key="Q8"
              title="Unlock Growth with Razorpay"
              onClick={() => {
                sendEventToSegment('Q8');
              }}
            >
              Get offers worth ₹35 lakh: Accept payments worth ₹1 lakh for free, get fee waivers to
              disburse vendor payouts worth ₹33+ lakh, working capital loans from 1.25% per month
              and more!
              <br />
              <br />
              <Text weight="bold" color="shade.970" size="medium">
                1. Who all can avail the offer under "Unlock Growth with Razorpay"?
              </Text>
              Every business, whether you are a registered business (private limited, public
              limited, proprietorship, etc.) or an unregistered business(freelancer, consultant,
              homepreneur, etc.), you can sign up and avail these offers.
              <br />
              <br />
              <Text weight="bold" color="shade.970" size="medium">
                2. What offers are available under this campaign?
              </Text>
              Free credits of up to INR 1,00,000 on Razorpay payment products. Slashed pricing on
              Smart Collect, Route, and Subscription products. INR 2000 fee waiver on RazorpayX
              Virtual Account and INR 3000 additional fee waiver on RazorpayX Current Account. 3
              months free trial on Opfin. 1 month free trial and an additional 10% off on the first
              3 paid months. Working capital loans starting at 1.25% per month.
              <br />
              <br />
              <Text weight="bold" color="shade.970" size="medium">
                3. What all payment modes are supported by Razorpay?
              </Text>
              Razorpay supports over 100+ payment modes including Credit and Debit Cards (Visa,
              Mastercard, Rupay, AMEX, Diners), Net Banking from top 50+ banks, UPI (Web Collect
              &amp; UPI Intent), Online Wallets, EMI and NEFT/RTGS payments.
              <br />
              <br />
              <Text weight="bold" color="shade.970" size="medium">
                4. Is this offer valid for a lifetime?
              </Text>
              This offer is applicable to all signups till 31st July 2020. The benefits, free
              credits, and free trials can be availed for up to 1 year.
              <br />
              <br />
              <Text weight="bold" color="shade.970" size="medium">
                5. What documents are required to sign up on Razorpay?
              </Text>
              To get started with Razorpay, all you need is your PAN card, Aadhar Card, or Driving
              license or Voter ID (any one of the three) and in some cases, a canceled cheque.
              <br />
              <br />
              <Text weight="bold" color="shade.970" size="medium">
                6. What platforms does Razorpay support?
              </Text>
              Razorpay helps you accept online payments from customers across Desktop, Mobile web,
              Android &amp; iOS. Additionally, by using Razorpay Payment Links, you can collect
              payments across multiple channels like SMS, Email, Whatsapp, Chatbots &amp; Messenger.
              <br />
              <br />
              <Text weight="bold" color="shade.970" size="medium">
                7. What are the terms and conditions for this offer?
              </Text>
              You can read the detailed terms and conditions{' '}
              <Link href="https://razorpay.com/links/unlock-growth-terms-and-conditions">here</Link>
              .
            </Panel>
            <Space padding={[1.5, 0, 0.5]}>
              <View>
                <Heading size="medium" color="shade.970">
                  KYC Review Duration
                </Heading>
              </View>
            </Space>
            <Panel
              key="Q9"
              title="I have signed up with Razorpay. How do I complete my activation form?"
              onClick={() => {
                sendEventToSegment('Q9');
              }}
            >
              You can login to your Razorpay account and refer to this{' '}
              <Link href="https://i.imgur.com/prVyTF9.gif">short animation</Link> to find and
              complete the activation form in your dashboard.
            </Panel>
            <Panel
              key="Q10"
              title="My account is not yet activated, it's been too long since I got any update, what do I do?"
              onClick={() => {
                sendEventToSegment('Q10');
              }}
            >
              We try our best to have everyone's account activated on time, however since you
              haven’t received an update on this, please file a grievance over here:{' '}
              <Link href="https://razorpay.com/contact/#grievance">here</Link> with your merchant ID
              and our team will update you at the earliest on the status of your account.
            </Panel>

            <Panel
              key="Q11"
              title="I have submitted my activation form but my account is not activated. Can I start integrating?"
              onClick={() => {
                sendEventToSegment('Q11');
              }}
            >
              Yes you can. Please refer to our integration documentation at{' '}
              <Link href="https://razorpay.com/integrations/">
                https://razorpay.com/integrations/
              </Link>
              . If you face any issue during the integration, please raise a request{' '}
              <Link href="https://razorpay.com/support/#request">here</Link> and our team will get
              back to you on this.
            </Panel>
            <Panel
              key="Q12"
              title="I had submitted the form long back. How do I get my account activated?"
              onClick={() => {
                sendEventToSegment('Q12');
              }}
            >
              Please check if you have received a mail from us around the time you submitted the
              activation form and revert on the mail thread. If you don’t see any mail, please raise
              a request over:
              <br /> <Link href="https://razorpay.com/contact/">
                https://razorpay.com/contact/
              </Link>{' '}
              and our team will get back to you.
            </Panel>
            <Panel
              key="Q13"
              title="Do you support unregistered businesses?"
              onClick={() => {
                sendEventToSegment('Q13');
              }}
            >
              Yes, we do support freelancers/individuals/unregistered business entities. You can
              sign up <Link href="https://dashboard.razorpay.com/#/access/signup">here</Link> and
              submit your activation form. Get started on accepting payments from your customers
              now!
              <br />
              <br />
              Keywords: individual, individual business, individual account, individual entity,
              unregistered business, unregistered, Freelancer
            </Panel>
            <Panel
              key="Q14"
              title="What are the payment methods supported?"
              onClick={() => {
                sendEventToSegment('Q14');
              }}
            >
              We support payments through Cards, Netbanking, UPI and Wallets. You may find more
              about this <Link href="https://razorpay.com/payment-gateway/#methods">here</Link>
            </Panel>
          </StatelessAccordian>
        </ModalBody>
      </>
    </Modal>
  );
};

export default FAQs;
