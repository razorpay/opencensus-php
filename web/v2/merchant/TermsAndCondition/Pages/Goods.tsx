import React from 'react';
import { SubHeader } from './index';
import { InLineText, ContentWrapper, List, Description, TnCLink } from './Styled';
import { states } from 'v2/merchant/onboarding/mobile/Constants/OnboardingConstants';

interface GoodsTypePropsT {
  context: any;
}

const GoodsType: React.FC<GoodsTypePropsT> = ({ context }) => {
  return (
    <>
      <SubHeader>Introduction and Terms of Use</SubHeader>
      <Description color="shade.970">
        These <InLineText weight="bold">TERMS AND CONDITIONS (“T&Cs”)</InLineText> govern the
        purchase of Goods (defined hereinafter) from the Company (defined hereinafter). These T&Cs
        include, and incorporate by this reference, the policies and guidelines referenced below.
        Company (defined hereinafter) reserves the right to change or revise these T&Cs at any time
        by posting any changes or a revised T&Cs on its website/ payment page, etc. and shall be
        effective immediately, unless stated otherwise. The use of the Company’s website following
        the posting any such changes or of revised T&Cs will constitute the acceptance of any such
        changes or revisions. Company encourages the user/ you to review these T&Cs in order to
        understand the terms and conditions governing the purchase of the Goods. These T&Cs do not
        alter in any way the terms or conditions of any other written agreement that the Buyer
        (defined hereinafter) may have with Company for other products or services.
      </Description>
      <ContentWrapper>
        <SubHeader>Definitions</SubHeader>
        <Description color="shade.970">
          In these conditions the following words shall have the meanings shown
        </Description>
        <br />
        <ul>
          <List>
            <Description color="shade.970">
              <InLineText weight="bold">“Buyer”</InLineText> means any person, firm, or company
              purchasing Goods (defined hereinafter) from the Company under the Contract (defined
              hereinafter).
            </Description>
          </List>
          <br />
          <List>
            <Description color="shade.970">
              <InLineText weight="bold">“Company”</InLineText> means{' '}
              {context ? context?.business_name : 'ABC Corp L.T.D'} with its registered office at{' '}
              {context
                ? `${context?.business_registered_address}, ${context?.business_registered_city} ${
                    states[context?.business_registered_state]
                  }, Pin-${context?.business_registered_pin}`
                : 'H-23, first block, Kormangla Banglore, Karnataka Pin-560047'}{' '}
              and website at{' '}
              {context ? (
                <TnCLink href={context?.link}>{context?.link}</TnCLink>
              ) : (
                'https://tnc.razorpay.com/tnc/HFO0JH8G98'
              )}{' '}
              and/ or one of its associate or subsidiary companies as the case may be. The nature of
              business of this company is declared as belonging to{' '}
              {context ? context?.business_subcategory : 'Horizontal Commerce/Marketplace'} within{' '}
              {context ? context?.business_category : 'Ecommerce'}. Description of business is as
              follows :{' '}
              {context
                ? context?.business_model
                : 'My business is used for ecommerce online plateform'}
            </Description>
          </List>
          <br />
          <List>
            <Description color="shade.970">
              <InLineText weight="bold">“Contract”</InLineText> means the contract concluded by the
              Company and Buyer for the supply of Goods either as specified in the Company’s
              pertinent invoice or as otherwise contemplated therein, whether expressly or
              impliedly, including by actual acceptance of the Goods by Buyer and/or by any payment
              therefor, whereby it is expressly agreed that the conclusion of which shall be deemed
              to constitute full consent to performing all transactions contemplated thereby on the
              sole and exclusive basis of these T&Cs, unless otherwise confirmed in writing by the
              Company.
            </Description>
          </List>
          <br />
          <List>
            <Description color="shade.970">
              <InLineText weight="bold">“Goods”</InLineText>
              means any products or items purchased by the Buyer from the Company and/or products or
              items manufactured, imported, supplied and/or delivered for by the Company to the
              Buyer.
            </Description>
          </List>
        </ul>
      </ContentWrapper>
      <ContentWrapper>
        <SubHeader>General</SubHeader>
        <Description color="shade.970">
          These conditions shall be deemed to be incorporated in all Contracts of the Company to
          sell Goods and together with any special condition appearing on the face of the Company’s
          invoice. In the case of any inconsistency with any order, letter or form of Contract sent
          by the Buyer to the Company or any other communication between the Buyer and the Company
          whatever may be their respective dates, the provisions of these conditions shall prevail
          unless expressly varied in writing and signed by an authorized representative on behalf of
          the Company. <br /> <br /> Any concession made by the Company to the Buyer, unless
          expressly varied in writing and signed by an authorized representative on behalf of the
          Company, shall not affect the strict rights of the Company under the Contract. If, in any
          particular case, any of these conditions shall be held to be invalid or shall not apply to
          the Contract the other conditions shall continue in full force and effect. <br /> <br />{' '}
          Statement, description, information, warranty condition or recommendation contained in any
          Contract, catalogue, price list, advertisement or any communication or made verbally by
          any of the agents or employees of the Company shall not be construed to enlarge, vary or
          override in any way any of these T&Cs unless otherwise provided herein.
        </Description>
      </ContentWrapper>
      <ContentWrapper>
        <SubHeader>Goods</SubHeader>
        <Description color="shade.970">
          The website/ payment page offers for sale certain Goods. By placing an order for the Goods
          through the website/ payment page, the Buyer accepts the terms set forth in these T&Cs.{' '}
          <br /> <br /> The Company may have proprietary rights and trade secrets in the Goods. The
          Buyer is prohibited from copying, reproducing, reselling or redistributing any Goods
          manufactured and/or distributed by the Company, unless expressly consented otherwise. The
          Company also has rights to all trademarks and logos and specific layouts of this website/
          payment page, including calls to action, text placement, images and other information.{' '}
          <br /> <br /> The Buyer shall be responsible for paying any applicable taxes on purchasing
          any Goods from the website/ payment page, unless otherwise mentioned expressly.
        </Description>
      </ContentWrapper>
      <ContentWrapper>
        <SubHeader>Buyer’s Responsibility</SubHeader>
        <Description color="shade.970">
          The Buyer is solely responsible for satisfying itself that the data supplied by it to the
          Company, on which any information or recommendation(s) made by the Company is based, is
          correct and that any assumptions made by the Company to supplement such data are suitable
          for the Buyer’s purposes. The Company accepts no responsibility of any nature whatsoever
          for information or advice it supplies or where any data supplied by the Buyer is incorrect
          or where any assumption, which the Company has made, is unsuitable for the Buyer’s
          purposes.
        </Description>
      </ContentWrapper>
      <ContentWrapper>
        <SubHeader>Orders</SubHeader>
        <Description color="shade.970">
          The prices payable for Goods shall unless otherwise stated by the Company in writing in
          the Contract and agreed on its behalf be the trade price list of the Company current at
          the date of dispatch of the Goods and in the case of an order for delivery by instalments
          the price payable for each instalment shall be the list price of the Company current at
          the date of the dispatch of such instalment of the Goods unless the price is otherwise
          expressly stated in the Contract to be firm for a fixed period. <br /> <br /> Unless
          otherwise expressly stated to be firm for a fixed period the Company’s prices are subject
          to variation. The Company accordingly reserves the right to adjust the invoice price by
          the amount of any increase or decrease in costs.
        </Description>
      </ContentWrapper>
      <ContentWrapper>
        <SubHeader>Delivery</SubHeader>
        <Description color="shade.970">
          Any delivery dates noted on the Company’s pertinent invoice are subject to reasonable
          adjustment. The acceptance of shipment by a logistics partner shall constitute proper
          delivery. Risks associated with the Goods shall pass to Buyer on delivery, upon any
          collection of the Goods by the Buyer or with the passing of title in the Goods, whichever
          occurs first; provided however, that where delivery is delayed due to circumstances caused
          by or within the responsibility of Buyer, risk of loss shall pass to Buyer upon Company’s
          notification that Goods are ready for dispatch. Unless otherwise specified in writing in
          Company’s pertinent invoice or Contract, all charges, expenses or taxes associated with
          the delivery shall be paid by the Buyer.
        </Description>
      </ContentWrapper>
      <ContentWrapper>
        <SubHeader>Passing of Title</SubHeader>
        <Description color="shade.970">
          From the date of delivery to the Buyer the Goods shall be at the risk of the Buyer who
          shall be solely responsible for their custody and maintenance but unless otherwise
          expressly agreed to in writing the Goods shall remain the property of the Company until
          all payments under the Contract have been made in full and unconditionally and credited to
          the Company’s account. Whilst the ownership of the Company continues the Buyer shall keep
          the Goods separate and identifiable from all other goods in its possession. <br /> <br />
          In the event of failure to pay the price in accordance with the Contract the Company shall
          have the right to re-sell the Goods. Such right shall be additional to (and not in
          substitution for) any other right of sale arising by operation or law or implications or
          otherwise.
        </Description>
      </ContentWrapper>
      <ContentWrapper>
        <SubHeader>Warranty and Limitation of Liability of Goods</SubHeader>
        <Description color="shade.970">
          All Goods are sold with the benefit and subject to the conditions of the warranty supplied
          with them, which is available for inspection on request. The warranty is limited to
          defects which the Buyer establishes to the Company’s reasonable satisfaction
          {context
            ? context.warranty_period === 'NA'
              ? ". we don't have a warranty"
              : ` within ${context.warranty_period}`
            : ' within 3 months'}{' '}
          from the date of delivery of the Goods unless otherwise specified{' '}
          <InLineText weight="bold">(“Warranty Period”).</InLineText> <br /> <br /> If the Buyer
          establishes to the Company’s reasonable satisfaction within the Warranty Period that there
          is a legitimate defect in the materials of the Goods, then the Company may at its sole
          discretion:
        </Description>
        <br />
        <ul>
          <List>
            <Description color="shade.970">
              Repair or make good such defect or failure in such Goods free of charge to the Buyer;
              or
            </Description>
          </List>
          <br />
          <List>
            <Description color="shade.970">
              Replace such Goods with Goods which are in all respects in accordance with the
              Contract; or
            </Description>
          </List>
          <br />
          <List>
            <Description color="shade.970">
              Replace such Goods with Goods which are in all respects in accordance with the
              Contract; or
            </Description>
          </List>
          <br />
          <List>
            <Description color="shade.970">
              Issue a credit note to the Buyer in respect of the whole or part of the Contract price
              or such Goods as appropriate having taken back such Goods.
            </Description>
          </List>
        </ul>
        <br />
        <Description color="shade.970">
          Provided that the liability of the Company under this Clause 8 shall in no event exceed
          the purchase price of such Goods and performance of any one of the above options shall
          constitute an entire discharge of the Company’s liability under this warranty. <br />
          <br /> Nothing herein or in any warranty given by the Company shall impose any liability,
          including for the loss of life or tangible and intangible property, upon the Company in
          respect of any defect in the Goods arising out of the act(s), omission(s), commission(s),
          negligence or default of the Buyer, its employees, servants, and/ or agents including in
          particular but without prejudice to the generality of the foregoing, any failure by the
          Buyer to comply with any recommendations / instructions of the Company as to storage and
          handling or use or surviving of the Goods, use of the Goods with other goods which are
          unsuitable for the Buyer’s purpose, or other misuse of the Goods or accident or wear and
          tear of the Goods.
        </Description>
      </ContentWrapper>
      <ContentWrapper>
        <SubHeader>Returns and Refunds</SubHeader>
        <Description color="shade.970">
          In case, the Buyer is dissatisfied with the purchased Goods, or in the event where there
          are defects and deficiencies in the Goods (attributable to, and accepted by the Company
          after due verification at its sole discretion), the Buyer may initiate a request for
          returning the Goods on the website/ payment page or by writing to our customer care at{' '}
          {context ? context?.email : 'abc.support@gmail.com'}. The Buyer shall initiate such
          requests for a return not later than{' '}
          {context ? context?.refund_request_period : '3-5 days'} from the date on which he/she
          received the delivery of the Goods. While raising a request for return on the website/
          payment page, the Buyer shall have the option to seek a refund of the money paid by
          him/her towards the purchase of the Goods. The Buyer will be required to produce a copy of
          the original invoice at the time of placing a request for return or exchange of Goods. The
          Buyer shall ensure that the Goods being returned comply with the conditions specified for
          the return or exchange of Goods. <br /> <br /> The Buyer is permitted to fully or
          partially cancel orders prior to its dispatch. Prior to the dispatch of the purchased
          Goods, should the Buyer decide to cancel the purchase, the Buyer can do so by contacting
          the Company. In all events of cancellation, prior to the dispatch of the purchased Goods,
          the Company shall initiate refunds within{' '}
          {context ? context?.refund_process_period?.replace(' days', '') : '5-8'} business days
          from the date on which it received the request from the Buyer. The refund will reflect in
          the Buyer’s bank account and/or the Buyer’s store credit within such reasonable time
          (subject to the policies of the Buyer’s bank in case of bank account/credit card refunds)
          from the date on which the Company initiates the refund. All refunds, except for refund to
          store credit, shall be subject to applicable charges as may be deducted by the Buyer’s
          bank. <br /> <br /> In case, the Buyer intends to return Goods and request for refund, the
          Company shall initiate a process of refund of the money paid by the Buyer towards purchase
          of Goods, if upon conducting quality checks, it is satisfied that the Goods being returned
          entitles the Buyer to a refund. It is further clarified that the Company shall not be
          required to make any refund in respect of any Goods that it deems ineligible for a refund
          based on such quality checks. The Company shall, subject to the satisfactory completion of
          required quality checks on the returned Goods, initiate a refund request. If the request
          for refund is undisputed by the Company, the refund should reflect in the Buyer’s bank
          account and/or the Buyer’s store credit within such reasonable time (subject to the
          policies of the Buyer’s bank in case of bank account/credit card refunds) from the date on
          which the Company initiates the refund. <br /> <br /> Return of purchased Goods is
          facilitated through the Company’s reverse-logistics partners. Upon the Buyer making a
          request for return of Goods on the website/ payment page and the same being duly
          acknowledged by the Company, the Company’s reverse-logistics partners shall get in touch
          with the Buyer in order to collect the purchased Goods from the Buyer and delivering it to
          the Company. However, in case of unavailability of the Company’s reverse-logistics
          partners, the Buyer shall bear the cost of returning the Goods to the Company.
        </Description>
      </ContentWrapper>
      <ContentWrapper>
        <SubHeader>Consequential Loss</SubHeader>
        <Description color="shade.970">
          The Company shall not be liable for any indirect, special or consequential losses
          (including, but not limited to loss of profit, revenue or other economic loss), costs,
          claims, liabilities or expenses of any nature whatsoever, whether arising out of any
          tortious act or omission or of any breach of Contract or statutory duty or duty of care or
          any misrepresentation or of any other causes whether or not known to the Company, and
          calculated by reference to profits, income, production or accruals or loss or accrual of
          such costs, loss or damage on a time basis or otherwise. <br /> <br /> The aggregate
          liability of the Company (whether in contract, tort, negligence or breach of statutory
          duty or otherwise) to the Buyer for any direct loss or damage shall be limited to the
          price of the specific Goods purchased under Contract only.
        </Description>
      </ContentWrapper>
      <ContentWrapper>
        <SubHeader>Force Majeure</SubHeader>
        <Description color="shade.970">
          The Company shall be entitled to delay or cancel delivery or to reduce the amount
          delivered if it is prevented from or hindered in or delayed in manufacturing, obtaining or
          delivering the Goods by normal route or means of delivery through any circumstances beyond
          its control including but not limited to an Act of God, fire, flood, lightning, strikes,
          lock-outs, accidents, war, revolution, acts of terrorism, riots, reduction in or
          unavailability of power at manufacturing plant, breakdown of plant or machinery or
          shortage or unavailability of fuel or raw materials from normal source of supply.
        </Description>
      </ContentWrapper>
      <ContentWrapper>
        <SubHeader>General</SubHeader>
        <Description color="shade.970">
          These T&Cs shall be binding upon and inure to the benefit of the respective successors and
          assigns of each of the parties hereto, but shall not be assigned or otherwise transferred,
          in whole or in part, by Buyer without the prior written consent of Company. <br /> <br />
          No waiver of any right under these T&Cs shall be deemed effective unless the same is set
          forth in writing signed by the Company. No waiver of any breach of these T&Cs will be
          treated as a waiver of any subsequent breach of these T&Cs. <br /> <br /> Unless otherwise
          agreed between the parties, the Buyer and Company agree that any sale pursuant to these
          T&Cs shall be deemed to have been made and executed in{' '}
          {context ? states[context?.business_registered_state] : 'Karnataka'} and that this
          contract and any disputes hereunder shall be governed, interpreted and construed in
          accordance with the laws of India. Any dispute arising under these T&Cs shall be
          exclusively submitted to the court of competent jurisdiction in{' '}
          {context ? states[context?.business_registered_state] : 'Karnataka'}.
          <br /> <br /> In the event any provision of these T&Cs are declared invalid or
          unenforceable, the remaining provisions will continue to apply and will retain their
          validity and significance. In such case(s) the parties will, to the extent possible,
          replace in good faith the invalid and/or unenforceable provision(s) with valid
          provision(s) which legally and economically are the closest to the desired purpose and
          intent of such invalid and/or unenforceable provision(s).
        </Description>
      </ContentWrapper>
      <br />
    </>
  );
};

export default GoodsType;
