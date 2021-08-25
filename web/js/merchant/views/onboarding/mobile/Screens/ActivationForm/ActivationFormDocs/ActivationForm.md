## ActivationForm

It renders the ContactDetails form BusinessOverview form BusinessDetails form BankDetails form and DocumentUpload form to get details of merchants.

it uses

1. isContactDetailsCompleted , isBusinessOverviewCompleted , isBusinessDetailsCompleted , isBankAndCompanyDetailsCompleted and isDocumentsUploadCompleted states to determine which tab to show when merchant lands on Activation form page.

2. isOpen to set whether to show FAQs component or not.

3. activeTabId to determine which tab to show currently.

4. IsSaveAndExitModalOpen to set whether to open SaveAndExitModal or not.

5. isEnableSettlementModalOpen to set whether to open EnableSettlementModalOpen or not

6. isSubmitFormModalOpen to set whether to open SubmitFormModalOpen or not

After the submission of business details form activation_data.activation_flow is updated to L1 state.
