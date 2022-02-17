import React from 'react';
import SlideContoller from './SlideController';

const s3 = (props) => {
  return (
    <>
      <div className="partner-onbr-info">
        <div class="title">Terms And Conditions</div>
        <div class="tnc-box">
          <ol>
            <li>
              <p>
                THIS ONLINE AGREEMENT is executed and effective as on the day when the partner agree
                to the terms and conditions mentioned in this document. the agreement is between:
              </p>
              <p>
                RAZORPAY SOFTWARE PRIVATE LIMITED, a company incorporated under the Companies Act,
                1956 and having its registered office address at 1st Floor, SJR Cyber, 22, Laskar
                Hosur Road, Opp. Adugodi Police Station, Adugodi, Bangalore – 560030 (hereinafter
                referred to as “First Party” or “Razorpay” which means and include, unless repugnant
                to the context or meaning thereof mean and include its liquidators, successors,
                receivers and assigns) of the ONE PART;
              </p>
              <p>AND</p>
              <p>
                The Individual/ Company registered for the partnership program through the Razorpay
                Portal (hereinafter referred to as "Second Party" or “Partner” which means and
                include, unless repugnant to the context or meaning thereof mean and include its
                affiliates, assigns, liquidators, successors and permitted assigns) of the OTHER
                PART.
              </p>
              <p>
                “First Party” or “Razorpay” and “Second Party” or "Partner” are hereinafter
                individually and collectively referred to as “Party” and “Parties” respectively, as
                the context may require.
              </p>
              <p>WHEREAS</p>
              <ol>
                <li>
                  Razorpay has developed and is the owner of certain software through which it
                  enables merchants to automate acceptance of payments vide pre-paid cards, credit
                  cards, debit cards, bank accounts, online wallets, Unified Payment Interface
                  (UPI), Razorpay Route, Razorpay Smart Collect, Subscription, etc. (“Razorpay
                  Services”). Razorpay is engaged in the business of providing payment platform and
                  payment aggregation services. Razorpay has also developed a system that enables
                  businesses to collect funds electronically from customers for selling goods or
                  providing services through the use of internet, applications, SMS or otherwise.
                </li>
                <li>
                  The Second Party is <em>inter alia</em> engaged in the business of technology
                  consulting The Second Party has agreed to introduced to Razorpay, Merchants
                  (defined hereinafter) who are procuring the Partner’s services and in connection
                  with the Merchant’s business require Razorpay Services.
                </li>
                <li>
                  Razorpay agrees to provide Razorpay Services to the Merchants and to that end the
                  Second Party has agreed to partner with Razorpay by marketing, assisting in
                  signing up and integrating Merchants with Razorpay Services.
                </li>
                <li>
                  The Parties have, after discussions, arrived at an arrangement wherein the Second
                  Party shall endeavor to promote Razorpay Services among Merchants (defined
                  hereinafter) (the services pertaining to promotion of Razorpay Services by the
                  Second Party among its clients and customers is hereinafter referred to as
                  “Services”). The Parties have decided to enter into this Agreement in order to
                  record their roles, responsibilities and obligations in connection with the
                  rendering and procuring of the Services.
                </li>
              </ol>
            </li>
            <li>
              <p>
                NOW THEREFORE, in consideration of the mutual covenants and agreements set forth in
                this Agreement and for other good and valuable consideration, the sufficiency of
                which is acknowledged by the Parties, the Parties hereby AGREE as follows:
              </p>
            </li>
            <li>
              <p>DEFINITIONS</p>
              <p>
                Unless the context otherwise provides or requires, the following words and
                expressions used in this Agreement shall have the meaning as provided to them herein
                below:
              </p>
              <ol>
                <li>
                  <p>
                    “Agreement” means this agreement, including the recitals, schedules, appendices,
                    annexures and exhibits and any amendments thereto from time to time.
                  </p>
                </li>
                <li>
                  <p>
                    “Business Day” means a day (other than Sunday, national holidays and bank
                    holidays in the Bangalore, India) on which nationalized banks are generally open
                    in Bangalore, India for the conduct of banking business and comprising of normal
                    working hours.
                  </p>
                </li>
                <li>
                  <p>“Confidential Information” shall mean all and any information:</p>
                  <ul>
                    <li>
                      which either Party may have or have acquired before or after the date of this
                      Agreement in relation to the Services and processes of either Party, any other
                      related information, trade secrets and all other information designated as
                      confidential by the Party from time to time;
                    </li>
                    <li>
                      which either Party may have acquired before or after the date of this
                      Agreement in relation to the customers, business, operations, financial
                      conditions, assets or affairs of the other Party resulting from: negotiating
                      this Agreement; or exercising its rights or performing its obligations under
                      this Agreement; or which relates to the contents of this Agreement (or any
                      agreement or arrangement entered into pursuant to this Agreement).
                    </li>
                  </ul>
                </li>
                <li>
                  <p>
                    “Person” means any individual, firm, company, governmental authority, joint
                    venture, partnership, association or other entity (whether or not having
                    separate legal personality).
                  </p>
                </li>
                <li>
                  <p>
                    “Merchants” shall mean any person or entity introduced or referred to Razorpay
                    by the Second Party and such persons or entities who enter into agreements with
                    Razorpay for availing of Razorpay Services.
                  </p>
                </li>
                <li>
                  <p>
                    “Customers” shall mean any person or entity who are availing services or
                    products of the Merchant using Razorpay Services.
                  </p>
                </li>
                <li>
                  <p>
                    “Onboarding” or “Integration” is the process that is required to be completed to
                    enable a Merchant to be registered on Razorpay’s platform pursuant to which the
                    Merchant would be able to avail of Razorpay Services.
                  </p>
                </li>
                <li>
                  <p>
                    “Razorpay Fees” means the minimum rates set out in Part A of Annexure I that are
                    chargeable by Razorpay as consideration for Razorpay Services.
                  </p>
                </li>
                <li>
                  <p>
                    “Razorpay Route” shall mean a software product developed and owned by Razorpay
                    which assists in split payments, makes vendor payouts, manages market place
                    money flow and much more through powerful APIs.
                  </p>
                </li>
                <li>
                  <p>
                    “Razorpay Smart Collect” shall mean a software product developed and owned by
                    Razorpay which assists in creation of virtual accounts and accepts payments via
                    NEFT, RTGS and IMPS. The software sends notification for each incoming payment
                    and automates the reconciliation process.
                  </p>
                </li>
                <li>
                  <p>
                    “Subscription” is a payments tool enabled by Razorpay’s platform which automates
                    recurring payments from the Customer’s account to the Merchant’s account through
                    different payment modes (such as credit card, debit card, direct debit, UPI,
                    etc.) .
                  </p>
                </li>
                <li>
                  <p>
                    “Transaction” shall mean a financial transaction conducted by the Customer
                    through Razorpay Services.
                  </p>
                </li>
              </ol>
            </li>
            <li>
              <p>TERMS</p>
              <ol>
                <li>
                  This Agreement shall be effective from the date of the execution of this Agreement
                  (hereinafter referred as the "Effective Date"). The Agreement shall be valid,
                  legal and binding on the Parties until terminated in accordance with terms of this
                  Agreement.
                </li>
              </ol>
            </li>
            <li>
              <p>SCOPE OF THE COLLABORATION</p>
              <ol>
                <li>
                  <p>The Agreement shall come into effect from the Effective Date.</p>
                </li>
                <li>
                  <p>
                    The Second Party shall register itself on Razorpay’s platform and obtain the
                    unique merchant identification (“Unique ID”) issued by the platform upon
                    registration.
                  </p>
                </li>
                <li>
                  <p>
                    Onboarding: In connection with Merchant Onboarding through the Second Party’s
                    website, the Second Party shall provide the necessary integration tools and
                    software to enable the Merchant to integration with Razorpay. The Partner shall
                    also cause the Merchant to provide the necessary KYC documents as prescribed by
                    Razorpay. Second Party acknowledges that delivery of KYC documents is a
                    prerequisite for Merchant Onboarding and that Razorpay is entitled to refuse
                    Merchant Onboarding on Second Party’s failure to obtain KYC documents.
                    Simultaneous with the Onboarding process, the Partner shall communicate the
                    Platform Fees offered to and as agreed between the Partner and the Merchant.
                  </p>
                </li>
                <li>
                  <p>
                    It is further agreed between the Parties that simultaneous with the Onboarding
                    being facilitated by the Second Party, the Second Party shall communicate to the
                    Merchants the requirement of the Merchants signing an agreement with Razorpay
                    (“Merchant Agreement”) and such Merchant Agreement would govern the terms and
                    conditions <em>inter se</em> the Merchants and Razorpay in relation to provision
                    and use of the Razorpay Services as also the Platform Fees. Razorpay reserves
                    the right to either not activate Razorpay Services or suspend Razorpay Services
                    if a Merchant does not enter into a Merchant Agreement with Razorpay. The
                    Partner shall communicate its ‘no-objection’ to Razorpay signing the Merchant
                    Agreement with the Merchants.
                  </p>
                </li>
                <li>
                  <p>
                    The Second Party agrees that as part of the Onboarding, Razorpay will conduct a
                    verification process on all Merchants before or simultaneous with the Onboarding
                    process and the Second Party shall assist Razorpay in conducting the
                    verification.
                  </p>
                </li>
                <li>
                  <p>
                    Access to information: Following the Merchant Onboarding, the Partner shall be
                    given access to Razorpay dashboard. Information pertaining to the Merchant
                    status shall be made available on the dashboard.
                  </p>
                </li>
              </ol>
            </li>
            <li>
              <p>RAZORPAY SERVICES</p>
              <ol>
                <li>
                  <p>
                    Razorpay shall provide its services solely based on the terms and conditions of
                    the agreement executed with the merchants/sub-merchants.
                  </p>
                </li>
                <li>
                  <p>
                    Notwithstanding anything mentioned in this clause, Razorpay does not make any
                    representations express or implied about the suitability of Razorpay Services
                    for the merchant’s/sub-merchant’s business.
                  </p>
                </li>
                <li>
                  <p>
                    The Second Party agrees that the customizations, if any, carried out for and on
                    behalf of any merchant/sub-merchant by Razorpay within the scope of Razorpay
                    Services, shall be Intellectual Property Rights of Razorpay and such additional
                    modifications can be used by such merchant/sub-merchant only upon obtaining due
                    permission, in writing by Razorpay.
                  </p>
                </li>
                <li>
                  <p>
                    Except as provided under this Agreement, no other rights as such is granted to
                    the Second Party under this Agreement.
                  </p>
                </li>
              </ol>
            </li>
            <li>
              <p>PARTNER SERVICES</p>
              <ol>
                <li>
                  <p>
                    The Partner shall endeavor to identify suitable merchants and refer them to
                    Razorpay from time to time.
                  </p>
                </li>
                <li>
                  <p>
                    The Partner shall create and establish a suitable partner program containing the
                    service offerings of Razorpay and make the program available its customers,
                    merchants, etc. in order to facilitate the customers, merchants, etc. to under
                    Razorpay’s products and services.
                  </p>
                </li>
                <li>
                  <p>
                    The Partner shall not engage in any activity or perform any act which may
                    disparage Razorpay or cause the Partner’s customers, merchants, etc. not to
                    consider Razorpay’s products and services or in any manner disincentivize
                    procuring of Razorpay’s products and services.
                  </p>
                </li>
              </ol>
            </li>
            <li>
              <p>RAZORPAY FEES</p>
              <ol>
                <li>
                  <p>
                    In consideration for the Razorpay Services, Razorpay shall deduct the Platform
                    Fee from the Customer Payment Amount in respect of every Transaction.
                  </p>
                </li>
                <li>
                  <p>
                    In consideration for the Merchant referrals by the Second Party, Razorpay shall
                    pay a commission or fee (“Service Fees”) as specified in Part A of Annexure I.
                  </p>
                </li>
                <li>
                  <p>
                    Second Party will raise a monthly invoice for the Service Fees and Razorpay
                    shall pay the Service fees within thirty (30) days of receiving an invoice from
                    Second Party and such payments shall be subject to applicable taxes as per the
                    provisions of applicable law.
                  </p>
                </li>
                <li>
                  <p>
                    The Partner’s entitlement to fee in addition to the Service Fees, is shared in
                    the Annexure
                  </p>
                </li>
              </ol>
            </li>
            <li>
              <p>CONFIDENTIALITY</p>
            </li>
            <li>
              <p>Confidentiality Obligation</p>
              <ol>
                <li>
                  <p>
                    Both Parties shall keep confidential (and ensure that its officers, employees,
                    agents, affiliates and professional and other advisers keep confidential) any
                    Confidential Information. Both Parties shall not, and shall procure that none of
                    their directors, officers, employees, agents, affiliates or professional
                    advisers shall use Confidential Information for any purpose other than for the
                    provision of Services and for performance under this Agreement.
                  </p>
                </li>
                <li>
                  <p>Exceptions from Confidentiality Obligations:</p>
                  <p>The obligation of confidentiality under this Clause does not apply to:</p>
                  <ul>
                    <li>
                      <p>
                        information which is independently developed by a Party or acquired from a
                        third party to the extent that it is acquired otherwise than as a result of
                        a breach of this Clause and with the right to disclose the same;
                      </p>
                    </li>
                    <li>
                      <p>
                        the disclosure of information to the extent required to be disclosed by any
                        applicable law, any governmental authority to whose rules, orders or decrees
                        a Party is subject, any stock exchange rule or regulation or any binding
                        judgment, order, rule or requirement of any court, arbitral tribunal or
                        other competent authority;
                      </p>
                    </li>
                    <li>
                      <p>
                        the disclosure (subject to Clause 6.3) in confidence to the Party’s
                        officers, employees or agents of information required to be disclosed for a
                        purpose incidental to the Agreement;
                      </p>
                    </li>
                    <li>
                      <p>
                        Information which comes within the public domain (otherwise than as a result
                        of a breach of this Clause 6).
                      </p>
                    </li>
                  </ul>
                </li>
                <li>
                  <p>Employees, Agents and Advisers or any other persons:</p>
                  <ul>
                    <li>
                      <p>
                        Both Parties shall inform any officer, employee or agent or any professional
                        or other adviser advising it in relation to the matters referred to in the
                        Agreement, or to whom it provides Confidential Information, that such
                        information is confidential and shall instruct them to keep it confidential;
                        and not to disclose it to any third party (other than those persons to whom
                        it has already been disclosed in accordance with the terms of the
                        Agreement).
                      </p>
                    </li>
                    <li>
                      <p>
                        Any breach of this Clause by any person to whom such Information was
                        disclosed will be considered as breach of this Clause by the Party which
                        disclosed the Confidential Information to the concerned person.
                      </p>
                    </li>
                  </ul>
                </li>
                <li>
                  <p>Return of Confidential Information</p>
                </li>
                <li>
                  <p>
                    If the Agreement terminates, the disclosing Party may by notice require the
                    recipient Party to promptly return all Confidential Information.
                  </p>
                  <ul>
                    <li>
                      <p>
                        return all documents containing Confidential Information which have been
                        provided by or on behalf of the Party demanding the return of Confidential
                        Information; and
                      </p>
                    </li>
                    <li>
                      <p>
                        destroy any copies of such documents and any document containing or made
                        from or with reference to the Confidential Information and take all
                        reasonable steps to expunge all Confidential Information from any computer,
                        word processor or other device containing Confidential Information.
                      </p>
                    </li>
                  </ul>
                </li>
                <li>
                  <p>DATA, SYSTEM SECURITY AND COMPLIANCES</p>
                  <ul>
                    <li>
                      <p>
                        Security: Both Parties shall ensure that there are proper encryption and
                        security measures at their respective websites to prevent any hacking into
                        information pertaining to transactions contemplated under this Agreement.
                      </p>
                    </li>
                    <li>
                      <p>
                        Security Requirements: In availing the Services, the Parties declare, assure
                        and undertake to abide by the relevant security standards/ regulations/
                        requirements/guidelines which would be applicable to the conduct of the
                        transactions contemplated under this Agreement, including, without
                        limitation, (a) regulatory provisions as may be applicable from time to
                        time, (b) security measures and resultant hardware/ software upgrade
                        required for the purpose of ensuring security of Transactions in the course
                        of performance of this Agreement (c) maintenance, protection and
                        confidentiality of transaction data as may be imposed by any regulatory or
                        standards authority including pursuant to PCI DSS, as applicable, and any
                        modifications to or replacements of such programs that may occur from time
                        to time.
                      </p>
                    </li>
                  </ul>
                </li>
              </ol>
            </li>
            <li>
              <p>TERMINATION</p>
              <ol>
                <li>
                  <p>
                    Either Party (“Terminating Party”) may terminate this Agreement on the
                    occurrence of any of the following events:
                  </p>
                  <ul>
                    <li>
                      <p>
                        Immediately, if the non-Terminating Party is declared insolvent or bankrupt
                        or is unable to pay its debts or makes a composition with its creditors;
                      </p>
                    </li>
                    <li>
                      <p>
                        Immediately, if the non-Terminating Party is dissolved or wound up
                        compulsorily or if an order made or an effective resolution is passed for
                        the winding up of the such non-Terminating Party;
                      </p>
                    </li>
                    <li>
                      <p>
                        In case of any material breach of this Agreement by the non-Terminating
                        Party, after giving one month’s prior written notice to the non-Terminating
                        Party to rectify such breach and the non-Terminating Party is unable to
                        rectify such breach within such time.
                      </p>
                    </li>
                  </ul>
                </li>
                <li>
                  <p>
                    Either Party may terminate this Agreement for convenience at any time with one
                    month’s prior written notice.
                  </p>
                </li>
              </ol>
            </li>
            <li>
              <p>LIMITATION OF LIABILITY</p>
              <ol>
                <li>
                  In no event shall either Party be liable to the other Party for any consequential
                  loss or damage or loss of profit, business, revenue, goodwill or anticipated
                  savings arising out of the performance of the Services contemplated in this
                  Agreement.
                </li>
              </ol>
            </li>
            <li>
              <p>NOTICES AND CONTRACT REPRESENTATIVES:</p>
              <p>
                Any notice provided for in this Agreement shall be in writing and shall be (i) first
                transmitted by electronic transmission, and then confirmed by postage, prepaid
                registered post with acknowledgement due or by recognized courier service; or (ii)
                sent by postage, prepaid registered post with acknowledgement due or by recognized
                courier service, to the relevant party at its address set out below:
              </p>
              <p>In the case of notices to the First Party:</p>
              <p>Addressed to:</p>
              <pre>
                <code>
                  Legal Team 1st Floor, SJR Cyber, 22, Laskar Hosur Road, Opp. Adugodi Police
                  Station, Adugodi, Bangalore – 560030
                </code>
              </pre>
              <p>In the case of notices to the Second Party: Addressed to:</p>
              <p>
                As per provided in the Registration form by the partner All notices shall be deemed
                to have been validly given on (i) the business day immediately after the date of
                transmission with confirmed answer back, if transmitted by facsimile; or (ii) in
                case sub-clause (i) does not apply, the expiry of 7 (seven) business days after
                posting, if sent by post. Either Party may, from time to time, change its address or
                representative for receipt of notices provided for in this Agreement by giving to
                the other Parties not less than 10 (ten) days’ prior written notice.
              </p>
            </li>
            <li>
              <p>ASSIGNMENT AND SUB-CONTRACTING:</p>
              <ol>
                <li>
                  <p>
                    Neither Party shall assign this Agreement or any of its rights and obligations
                    hereunder, without the prior written consent of the other Party. Any such
                    attempted assignment without consent shall be null and void. A Party may assign,
                    without such consent, its rights and obligations under this Agreement to: (i) an
                    affiliate; or (ii) any entity which acquires all or substantially all of its
                    capital stock or assets related to this Agreement through purchase, merger,
                    consolidation, or otherwise. Any assignment in violation of the foregoing shall
                    be void.
                  </p>
                </li>
                <li>
                  <p>
                    This Agreement is and shall be binding upon and inure to the benefit of both
                    Parties and their respective legal representatives, successors and permitted
                    assigns with respect to all covenants herein.
                  </p>
                </li>
              </ol>
            </li>
            <li>
              <p>CORPORATE AUTHORITY/FURTHER ASSURANCES</p>
              <p>
                Each Party represents that it has taken all necessary corporate action to authorize
                the execution and consummation of this Agreement and will furnish the other Party
                with satisfactory evidence of same upon request. Each Party agrees to negotiate in
                good faith the execution of such other documents or agreements as may be necessary
                or desirable for the implementation of this Agreement and the effective execution of
                the transactions contemplated hereby, and shall continue to do so during the Term of
                this Agreement.
              </p>
            </li>
            <li>
              <p>FORCE MAJEURE</p>
              <ol>
                <li>
                  <p>
                    In the event either Party (the “Prevented Party”) is prevented from performing
                    its obligations under this Agreement by force majeure, such as earthquake,
                    typhoon, flood, public commotion, torrential rains, heavy winds, storms or other
                    acts of nature, fire, terrorist acts, threatened terrorists acts, explosion,
                    acts of civil or military authority including the inability to obtain any
                    required approvals or permits, strikes, riots, war, plagues, other epidemics, or
                    other unforeseen events beyond the Prevented Party’s reasonable control (an
                    “Event of Force Majeure”), the Prevented Party shall notify the other party
                    without delay and within fifteen (15) days thereafter shall provide detailed
                    information concerning such event and documents evidencing such event,
                    explaining the reasons for its inability to execute, or for its delay in the
                    execution of, all or part of its obligations under this Agreement.
                  </p>
                </li>
                <li>
                  <p>
                    If an Event of Force Majeure occurs, neither Party shall be responsible for any
                    damage, increased costs or loss which the other Party may sustain by reason of
                    such a failure or delay of performance, and such failure or delay shall not be
                    deemed a breach of this Agreement. The Prevented Party shall take reasonable
                    means to minimize or remove the effects of an Event of Force Majeure and, within
                    the shortest reasonable time, attempt to resume performance of the obligations
                    delayed or prevented by the Event of Force Majeure.
                  </p>
                </li>
              </ol>
            </li>
            <li>
              <p>DISPUTE RESOLUTION</p>
              <p>
                All disputes arising out of or in relation to this Agreement, including any question
                regarding its existence, validity or termination, which cannot be amicably resolved
                by the Parties within 15 days of being brought to their attention, shall be settled
                by arbitration governed by the provisions of Arbitration and Conciliation Act, 1996.
                The venue/seat of Arbitration shall be Bangalore and the language of arbitration
                shall be English. A dispute shall be deemed to have arisen when either Party
                notifies the other Party in writing to that effect.
              </p>
            </li>
            <li>
              <p>GOVERNING LAW AND JURISDICTION</p>
              <p>
                This Agreement, the construction and enforcement of its terms and the interpretation
                of the rights and duties of the Parties hereto shall be governed by the laws of
                India and shall be subject to the jurisdiction of courts in Bangalore. This
                Agreement is executed in English language which shall prevail over any translation
                thereof.
              </p>
            </li>
            <li>
              <p>NON-COMPETE &amp; NON SOLICITATION</p>
              <p>
                During the Term and for a period of sixty (60) months from the completion of the
                Term or earlier termination of the Agreement, the Second Party shall not directly or
                indirectly solicit, entice away or engage for itself or any third party any
                employees, agents, customers, merchants, vendors or consultants of Razorpay.
              </p>
            </li>
            <li>
              <p>COMPLIANCE WITH LAWS</p>
              <p>
                Each Party hereto agrees that it shall comply with all applicable laws in performing
                its obligations hereunder. If at any time during the Term of this Agreement, a Party
                is informed or information comes to its attention that it is or may be in violation
                of any applicable law (including any ordinance, regulation, code order, decree,
                judgment of any court, tribunal or other authority having competent jurisdiction),
                that Party shall immediately take all appropriate steps to remedy such violation and
                comply with such law, regulation, ordinance, code order, decree, judgment in all
                respects. Further, each Party shall establish and maintain all proper records
                (including, but without limitation, accounting records) required by any law, code of
                practice or corporate policy applicable to it from time to time.
              </p>
            </li>
            <li>
              <p>SUCCESSORS:</p>
              <p>
                This Agreement binds the successors and assigns of the respective Parties with
                respect to all covenants herein, and cannot be changed except by written agreement
                signed by both Parties.
              </p>
            </li>
            <li>
              <p>SEVERABILITY:</p>
              <p>
                In the event any one or more of the provisions of this Agreement shall, for any
                reason, be held to be invalid, illegal or unenforceable, the remaining provisions of
                this Agreement shall be unaffected, and the invalid, illegal or unenforceable
                provision(s) shall be replaced by a mutually acceptable provision(s), which being
                valid, legal and enforceable, comes closest to the intention of the Parties
                underlying the invalid, illegal or unenforceable provision(s).
              </p>
            </li>
            <li>
              <p>HEADINGS</p>
              <p>
                The headings in this Agreement are for purposes of reference only and shall not in
                any way limit or otherwise affect the meaning or interpretation of any of the terms
                hereof.
              </p>
            </li>
            <li>
              <p>COUNTERPARTS</p>
              <p>
                This Agreement may be executed in several counterparts, each of which shall be
                deemed to be an original, and all of which, when taken together, shall constitute
                one and the same instrument.
              </p>
            </li>
            <li>
              <p>MODIFICATION, AMENDMENT, SUPPLEMENT OR WAIVER</p>
              <ul>
                <li>
                  <p>
                    No modification, amendment, supplement to or waiver of this Agreement or any of
                    its provisions shall be binding upon the Parties hereto unless made in writing
                    and duly signed by the Parties or Party against whom enforcement thereof is
                    sought.
                  </p>
                </li>
                <li>
                  <p>
                    A failure or delay of any Party to this Agreement to enforce at any time any of
                    the provisions of this Agreement or to exercise any option which is herein
                    provided, or to require at any time performance of any of the provisions hereof,
                    shall in no way be construed to be a waiver of such provisions of this
                    Agreement.
                  </p>
                </li>
              </ul>
            </li>
            <li>
              <p>ENTIRETY OF AGREEMENT</p>
              <p>
                This Agreement together with all Recitals, Appendices, Exhibits, Schedules,
                Attachments and Addenda (as applicable) attached hereto constitute the entire
                agreement between the Parties and supersedes all previous agreements, promises,
                representations, understandings and negotiations, whether written or oral, between
                the Parties with respect to the subject matter hereof.
              </p>
            </li>
            <li>
              <p>FURTHER ASSURANCES AND INTERPRETATION:</p>
              <ul>
                <li>
                  <p>
                    Each Party agrees to perform (or procure the performance of) all further acts
                    and things (including the execution and delivery of, or procuring the execution
                    and delivery of, all deeds and documents that may be required by law or as may
                    be necessary, required or advisable, procuring the convening of all meetings,
                    the giving of all necessary waivers and consents and the passing of all
                    resolutions and otherwise exercising all powers and rights available to them) to
                    implement and give effect to this Agreement.
                  </p>
                </li>
                <li>
                  <p>
                    Save as otherwise provided herein, nothing herein contained shall constitute or
                    be deemed to constitute any agency or partnership between or amongst any of the
                    Parties to this Agreement and no Party to this Agreement shall therefore act or
                    hold itself out as agent or partner of any other Party hereto.
                  </p>
                </li>
              </ul>
            </li>
          </ol>
          <p>
            As this is an electronic agreement, no signatures are required. Accepting the terms and
            conditions would be considered binding.
          </p>
          <ol>
            <li>
              <p>ANNEXURE I</p>
              <ul>
                <li>
                  <p>Part A</p>
                  <p>(Clause 1(h))</p>
                  <p>RAZORPAY FEES (exclusive of Taxes)</p>
                  <p>
                    Razorpay will charge fixed commission on transactions to merchants via partners,
                    represented herein as “Razorpay Fees” for different modes of payments such as
                    Credit Cards, Debit Cards, Net Banking and Online Wallets. Razorpay Fees can be
                    revised from time to time as per mutual agreement between the parties to this
                    Agreement.
                  </p>
                  <table>
                    <thead>
                      <tr>
                        <th>Particulars</th>
                        <th style={{ textAlign: 'right' }}>Charges in INR</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>Domestic Credit Cards</td>
                        <td style={{ textAlign: 'right' }}>1.9%</td>
                      </tr>
                      <tr>
                        <td>Debit Cards</td>
                        <td style={{ textAlign: 'right' }}>1.9%</td>
                      </tr>
                      <tr>
                        <td>Net Banking</td>
                        <td style={{ textAlign: 'right' }}>1.9%</td>
                      </tr>
                      <tr>
                        <td>Online Wallets</td>
                        <td style={{ textAlign: 'right' }}>1.9%</td>
                      </tr>
                      <tr>
                        <td>UPI</td>
                        <td style={{ textAlign: 'right' }}>1.9%</td>
                      </tr>
                      <tr>
                        <td>Amex / Diners / International Credit Cards</td>
                        <td style={{ textAlign: 'right' }}>2.90%</td>
                      </tr>
                      <tr>
                        <td>
                          <em>
                            applicable goods and service tax and any other taxes extra as per
                            government of India regulations
                          </em>
                        </td>
                        <td style={{ textAlign: 'right' }} />
                      </tr>
                    </tbody>
                  </table>
                </li>
                <li>
                  <p>PART B</p>
                  <p>Integration Fee:</p>
                  <p>
                    There is no integration fee for merchants that are onboarded through partners on
                    the default Partner Pricing.
                  </p>
                  <p>Annual Maintenance Fee:</p>
                  <p>
                    There is no Maintenance fee for merchants that are onboarded through partners on
                    the default Partner Pricing.
                  </p>
                  <p>Partner Commission</p>
                  <p>Payment Gateway (Collections):</p>
                  <p>
                    0.10% of the transaction by the Merchant (transacting directly and not via any
                    other Partner platform ) will be paid out as commission to the Partner.
                  </p>
                  <p>RazorpayX Current Account:</p>
                  <table>
                    <thead>
                      <tr>
                        <th>Product</th>
                        <th style={{ textAlign: 'center' }}>Closures/ month</th>
                        <th style={{ textAlign: 'center' }}>Incentive/ account*</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>RazorpayX Current Account</td>
                        <td style={{ textAlign: 'center' }}>1 - 10</td>
                        <td style={{ textAlign: 'center' }}>INR 1500</td>
                      </tr>
                      <tr>
                        <td>RazorpayX Current Account</td>
                        <td style={{ textAlign: 'center' }}>11 - 20</td>
                        <td style={{ textAlign: 'center' }}>INR 2000</td>
                      </tr>
                      <tr>
                        <td>RazorpayX Current Account</td>
                        <td style={{ textAlign: 'center' }}>21 - 30</td>
                        <td style={{ textAlign: 'center' }}>INR 3000</td>
                      </tr>
                    </tbody>
                  </table>
                  <ul>
                    <li>
                      Partner will be paid a commission which shall be payable only after RazorpayX
                      Current account activation
                    </li>
                    <li>
                      Minimum 5 payouts to be completed for RazorpayX Current Account Commission
                      payouts
                    </li>
                    <li>
                      Current Account is serviceable in the selected Pincode based on Bank’s
                      availability in the particular Pincode
                    </li>
                  </ul>
                </li>
              </ul>
            </li>
          </ol>
        </div>
      </div>
      <SlideContoller
        nextBtnLabel="Accept and Get Started"
        sliderProps={props.sliderProps}
        onNext={props.onNext}
      />
    </>
  );
};

export default s3;
