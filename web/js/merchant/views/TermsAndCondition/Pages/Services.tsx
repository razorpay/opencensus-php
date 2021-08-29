import React from 'react';
import { SubHeader, TncPagePropsT } from './index';
import { InLineText, ContentWrapper, List, Description, TnCLink } from './Styled';

const ServiceType: React.FC<TncPagePropsT> = ({
  isOrgAxis,
  state,
  address,
  businessName,
  tncLink,
  subcategory,
  category,
  businessModel,
  email,
  refundRequestPeriod,
  refundProcessPeriod,
}) => {
  return (
    <>
      <SubHeader>Introduction and Terms of Use</SubHeader>
      <Description color="shade.970">
        These <InLineText weight="bold">TERMS AND CONDITIONS (“T&Cs”)</InLineText> govern the
        provision of Services (defined hereinafter) available on the website/ payment page. These
        T&Cs include, and incorporate by this reference, the policies and guidelines referenced
        below. Company (defined hereinafter) reserves the right to change or revise these T&Cs at
        any time by posting any changes or a revised T&Cs on the {isOrgAxis ? '' : 'website/'}{' '}
        payment page and shall be effective immediately, unless stated otherwise.
        {isOrgAxis
          ? ''
          : 'The use of the website/ payment page following the posting any such changes or of revised T&Cs will constitute the acceptance of any such changes or revisions.'}{' '}
        Company encourages the user/ you to review these T&Cs in order to understand the terms and
        conditions governing the use of the website/ payment page. These T&Cs do not alter in anyway
        the terms or conditions of any other written agreement that the Customer (defined
        hereinafter) may have with Company for other products or services.
      </Description>
      <ContentWrapper>
        <SubHeader>Definitions</SubHeader>
        <Description color="shade.970">
          In these conditions the following words shall have the meanings shown:
        </Description>
        <br />
        <ul>
          <List>
            <Description color="shade.970">
              <InLineText weight="bold">“Company”</InLineText> means {businessName} with its
              registered office at {address} and website at{' '}
              <TnCLink href={tncLink}>{tncLink}</TnCLink> and/ or one of its associate or subsidiary
              companies as the case may be. The nature of business of this company is declared as
              belonging to {subcategory} within {category}. Description of business is as follows :{' '}
              {businessModel}
            </Description>
          </List>
          <br />
          <List>
            <Description color="shade.970">
              <InLineText weight="bold">“Contract”</InLineText> means the contract concluded by the
              Company and Customer for the provision of Services either as specified in the
              Company’s pertinent invoice or as otherwise contemplated therein, whether expressly or
              impliedly, including by actual acceptance of the Services by Customer and/or by any
              payment therefor, whereby it is expressly agreed that the conclusion of which shall be
              deemed to constitute full consent to performing all transactions contemplated thereby
              on the sole and exclusive basis of these T&Cs, unless otherwise confirmed in writing
              by the Company.
            </Description>
          </List>
          <br />
          <List>
            <Description color="shade.970">
              <InLineText weight="bold">“Customer”</InLineText> means any person, firm, or company
              availing Services from the Company under the Contract.
            </Description>
          </List>
          <br />
          <List>
            <Description color="shade.970">
              <InLineText weight="bold">“Services”</InLineText> means the services to be provided by
              the Company to the Customer under the Contract.
            </Description>
          </List>
        </ul>
      </ContentWrapper>
      <ContentWrapper>
        <SubHeader>General</SubHeader>
        <Description color="shade.970">
          These conditions shall be deemed to be incorporated in all Contracts of the Company to
          provide Services and together with any special condition appearing on the face of the
          Company’s invoice. In the case of any inconsistency with any order, letter or form of
          Contract sent by the Customer to the Company or any other communication between the
          Customer and the Company whatever may be their respective dates, the provisions of these
          conditions shall prevail unless expressly varied in writing and signed by an authorized
          representative on behalf of the Company. <br /> <br /> Any concession made by the Company
          to the Customer, unless expressly varied in writing and signed by an authorized
          representative on behalf of the Company, shall not affect the strict rights of the Company
          under the Contract. If, in any particular case, any of these conditions shall be held to
          be invalid or shall not apply to the Contract the other conditions shall continue in full
          force and effect. <br /> <br /> Statement, description, information, warranty condition or
          recommendation contained in any Contract, catalogue, price list, advertisement or any
          communication or made verbally by any of the agents or employees of the Company shall not
          be construed to enlarge, vary or override in any way any of these T&Cs unless otherwise
          provided herein.
        </Description>
      </ContentWrapper>
      <ContentWrapper>
        <SubHeader>Services</SubHeader>
        <Description color="shade.970">
          The website/ payment page offers certain Services. By booking the Services through the
          website/ payment page, the Customer accepts the terms set forth in these T&Cs. The Company
          also has rights to all trademarks and logos and specific layouts of this website/ payment
          page, including calls to action, text placement, images and other information. <br />{' '}
          <br /> The Customer shall be responsible for paying any applicable taxes on booking any
          Services on the website/ payment page, unless otherwise mentioned expressly.
        </Description>
      </ContentWrapper>
      <ContentWrapper>
        <SubHeader>Buyer’s Responsibility</SubHeader>
        <Description color="shade.970">
          The Customer is solely responsible for satisfying itself that the data supplied by it to
          the Company, on which any information or recommendation(s) made by the Company is based,
          is correct and that any assumptions made by the Company to supplement such data are
          suitable for the Customer’s purposes. The Company accepts no responsibility of any nature
          whatsoever for information or advice it supplies or where any data supplied by the
          Customer is incorrect or where any assumption, which the Company has made, is unsuitable
          for the Customer’s purposes.
        </Description>
      </ContentWrapper>
      <ContentWrapper>
        <SubHeader>Fees</SubHeader>
        <Description color="shade.970">
          The fees payable for Services shall be as mentioned in the Contract or website/ payment
          page unless otherwise stated by the Company in writing. <br /> <br /> Unless otherwise
          expressly stated in the Contract, the Company shall be entitled to be reimbursed by the
          Customer for all additional expenditures (including but not limited to any extra costs
          arising from or related to any delays in the completion of the Services stemming from the
          failure of the Customer to duly make available to the Company) reasonably and properly
          incurred in the performance of the Services.
        </Description>
      </ContentWrapper>
      <ContentWrapper>
        <SubHeader>Delivery</SubHeader>
        <Description color="shade.970">
          The Company shall determine the manner in which and the person by whom the Services will
          be carried out, taking into account, as far as is feasible, the reasonable requests
          expressed by the Customer. <br /> <br /> The Company shall complete the Services with
          reasonable skill, care and diligence in accordance with the Contract. <br /> <br /> The
          Customer hereby accepts that the time schedule allocated for the performance of the
          Services may be subject to change in case of amendment to the Contract and/or additional
          Services to be provided thereunder after conclusion of the Contract. <br /> <br /> In case
          of any change of circumstances under which the Contract is to be performed which cannot be
          attributed to the Company, the Company may make any such amendments to the Contract as it
          deems necessary to adhere to the agreed quality standard and specifications. Any costs
          arising from or related to this change of circumstances will be fully borne by the
          Customer. <br /> <br /> The Company may, at its discretion and, where possible, replace
          the person or persons charged with performing the Contract, if and in so far as the
          Company believes that such replacement would benefit the performance of the Contract.{' '}
          <br /> <br /> The Customer has the right to notify the Company that it wishes to modify
          its requirements in relation to the Contract. Such modifications shall not enter into
          effect until the Company and the Customer have agreed on the consequences thereof such as
          to the Contract fee and the completion date of the Contract.
        </Description>
      </ContentWrapper>
      <ContentWrapper>
        <SubHeader>Refunds</SubHeader>
        <Description color="shade.970">
          In case, the Customer intends to cancel the booking for Services and request for refund,
          the Company shall initiate a process of refund of the money paid by the Customer towards
          booking of Services, if the Customer places the request for refund within{' '}
          {refundRequestPeriod} of booking the Services, by writing to our customer care at {email}.
          It is further clarified that the Company shall not be required to make any refund in
          respect of any Services that it deems ineligible for a refund. If the request for refund
          is undisputed by the Company, the refund should reflect in the Customer’s bank account
          and/or the Customer’s store credit within {refundProcessPeriod} days/ such reasonable time
          (subject to the policies of the Customer’s bank in case of bank account/credit card
          refunds) from the date on which the Company initiates the refund.
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
          duty or otherwise) to the Customer for any direct loss or damage shall be limited to the
          price of the specific Services availed under Contract only.
        </Description>
      </ContentWrapper>
      <ContentWrapper>
        <SubHeader>Warranty and Limitation of Liability</SubHeader>
        <Description color="shade.970">
          The Company, and any person put forward by the Company to perform the Services, shall not
          be liable if the Services provided or the results generated are not absolutely correct,
          nor does the Company, or any person put forward by the Company to perform the Services,
          warrant, either expressed or implied, that the performance by him will not infringe upon
          intellectual property rights of any third party. <br /> <br /> The Company, nor any person
          put forward by the Company to perform the Services, shall not be responsible for any loss,
          destruction or damage of whatsoever nature (including injury or death) incurred by the
          Customer, its employees or third parties, except to the extent that the same can be shown
          to be due to gross negligence or wilful misconduct on the part of the Company or its
          agents or employees. <br /> <br /> In case the Company is found to be liable, the
          Company’s liability shall in aggregate not exceed the price for the Services. In any
          event, the Company shall not be liable to the Customer for any consequential, indirect,
          special, incidental or exemplary damages of any nature whatsoever that may be suffered by
          the other party.
        </Description>
      </ContentWrapper>
      <ContentWrapper>
        <SubHeader>Force Majeure</SubHeader>
        <Description color="shade.970">
          The Company shall be entitled to delay or cancel the provision of Services if it is
          prevented from or hindered in or delayed in performing the Services through any
          circumstances beyond its control including but not limited to an Act of God, fire, flood,
          lightning, strikes, lock-outs, accidents, war, revolution, acts of terrorism, riots.
        </Description>
      </ContentWrapper>
      <ContentWrapper>
        <SubHeader>General</SubHeader>
        <Description color="shade.970">
          These T&Cs shall be binding upon and inure to the benefit of the respective successors and
          assigns of each of the parties hereto, but shall not be assigned or otherwise transferred,
          in whole or in part, by Customer without the prior written consent of the Company. <br />{' '}
          <br /> No waiver of any right under these T&Cs shall be deemed effective unless the same
          is set forth in writing signed by the Company. No waiver of any breach of these T&Cs will
          be treated as a waiver of any subsequent breach of these T&Cs. <br /> <br />
          Unless otherwise agreed between the parties, the Customer and Company agree that any
          Services performed pursuant to these T&Cs shall be deemed to have been made and executed
          in {isOrgAxis ? address : state} and that this contract and any disputes hereunder shall
          be governed, interpreted and construed in accordance with the laws of India. Any dispute
          arising under these T&Cs shall be exclusively submitted to the court of competent
          jurisdiction in {state}.
          <br /> <br /> In the event any provision of these T&Cs is declared invalid or
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
export default ServiceType;
